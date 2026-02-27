<?php

declare(strict_types=1);

namespace Galaxon\Sniffs\Classes;

use Exception;
use Galaxon\Helpers\PropertyHookHelper;
use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Verifies that properties are declared correctly.
 *
 * This sniff is based on PSR2's PropertyDeclarationSniff but properly handles
 * PHP 8.4 property hooks. Variables inside property hook bodies are not
 * property declarations and should be ignored. Also, properties with hooks
 * don't end with a semicolon, they end with a closing brace.
 */
class PropertyDeclarationSniff extends AbstractVariableSniff
{
    /**
     * Processes the function tokens within the class.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processMemberVar(File $phpcsFile, int $stackPtr): void
    {
        // If this variable is inside a property hook body, skip it.
        // Variables inside hooks (like $this, $value, local vars) are not
        // property declarations.
        if (PropertyHookHelper::isInsidePropertyHook($phpcsFile, $stackPtr)) {
            return;
        }

        try {
            $propertyInfo = $phpcsFile->getMemberProperties($stackPtr);
        } catch (Exception) {
            // Parse error: property in enum. Ignore.
            return;
        }

        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['content'][1] === '_') {
            $error = 'Property name "%s" should not be prefixed with an underscore to indicate visibility';
            $data = [$tokens[$stackPtr]['content']];
            $phpcsFile->addWarning($error, $stackPtr, 'Underscore', $data);
        }

        // Detect multiple properties defined at the same time.
        // For properties with hooks, we need to handle the { ... } block.
        $find = Tokens::SCOPE_MODIFIERS;
        $find[] = T_VARIABLE;
        $find[] = T_VAR;
        $find[] = T_READONLY;
        $find[] = T_FINAL;
        $find[] = T_ABSTRACT;
        $find[] = T_SEMICOLON;
        $find[] = T_OPEN_CURLY_BRACKET;

        $prev = $phpcsFile->findPrevious($find, ($stackPtr - 1));
        if ($tokens[$prev]['code'] === T_VARIABLE) {
            return;
        }

        if ($tokens[$prev]['code'] === T_VAR) {
            $error = 'The var keyword must not be used to declare a property';
            $phpcsFile->addError($error, $stackPtr, 'VarUsed');
        }

        // Check for multiple property declaration.
        // We need to find the next T_VARIABLE, T_SEMICOLON, or T_OPEN_CURLY_BRACKET.
        // If we find T_OPEN_CURLY_BRACKET first, it's a property with hooks (not multiple).
        // If we find T_VARIABLE first before T_SEMICOLON, it's multiple properties.
        $next = $phpcsFile->findNext([T_VARIABLE, T_SEMICOLON, T_OPEN_CURLY_BRACKET], ($stackPtr + 1));
        if ($next !== false && $tokens[$next]['code'] === T_VARIABLE) {
            // Found another variable. But we need to check it's not inside a default value.
            // For example: public array $foo = [$bar] - $bar is not a second property.
            // Check if there's an = between stackPtr and next.
            $equals = $phpcsFile->findNext(T_EQUAL, ($stackPtr + 1), $next);
            if ($equals === false) {
                $error = 'There must not be more than one property declared per statement';
                $phpcsFile->addError($error, $stackPtr, 'Multiple');
            }
        }

        if ($propertyInfo['type'] !== '') {
            $typeToken = $propertyInfo['type_end_token'];
            $error = 'There must be 1 space after the property type declaration; %s found';
            if ($tokens[($typeToken + 1)]['code'] !== T_WHITESPACE) {
                $data = ['0'];
                $fix = $phpcsFile->addFixableError($error, $typeToken, 'SpacingAfterType', $data);
                if ($fix === true) {
                    $phpcsFile->fixer->addContent($typeToken, ' ');
                }
            } elseif ($tokens[($typeToken + 1)]['content'] !== ' ') {
                $next = $phpcsFile->findNext(T_WHITESPACE, ($typeToken + 1), null, true);
                if ($tokens[$next]['line'] !== $tokens[$typeToken]['line']) {
                    $found = 'newline';
                } else {
                    $found = $tokens[($typeToken + 1)]['length'];
                }

                $data = [$found];

                $nextNonWs = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($typeToken + 1), null, true);
                if ($nextNonWs !== $next) {
                    $phpcsFile->addError($error, $typeToken, 'SpacingAfterType', $data);
                } else {
                    $fix = $phpcsFile->addFixableError($error, $typeToken, 'SpacingAfterType', $data);
                    if ($fix === true) {
                        if ($found === 'newline') {
                            $phpcsFile->fixer->beginChangeset();
                            for ($x = ($typeToken + 1); $x < $next; $x++) {
                                $phpcsFile->fixer->replaceToken($x, '');
                            }
                            $phpcsFile->fixer->addContent($typeToken, ' ');
                            $phpcsFile->fixer->endChangeset();
                        } else {
                            $phpcsFile->fixer->replaceToken(($typeToken + 1), ' ');
                        }
                    }
                }
            }
        }

        if ($propertyInfo['scope_specified'] === false && $propertyInfo['set_scope'] === false) {
            $error = 'Visibility must be declared on property "%s"';
            $data = [$tokens[$stackPtr]['content']];
            $phpcsFile->addError($error, $stackPtr, 'ScopeMissing', $data);
        }

        // Check modifier ordering (same as PSR2).
        $hasVisibilityModifier = ($propertyInfo['scope_specified'] === true || $propertyInfo['set_scope'] !== false);
        $lastVisibilityModifier = $phpcsFile->findPrevious(Tokens::SCOPE_MODIFIERS, ($stackPtr - 1));
        $firstVisibilityModifier = $lastVisibilityModifier;

        if ($propertyInfo['scope_specified'] === true && $propertyInfo['set_scope'] !== false) {
            $scopePtr = $phpcsFile->findPrevious([T_PUBLIC, T_PROTECTED, T_PRIVATE], ($stackPtr - 1));
            $setScopePtr = $phpcsFile->findPrevious([T_PUBLIC_SET, T_PROTECTED_SET, T_PRIVATE_SET], ($stackPtr - 1));
            if ($scopePtr > $setScopePtr) {
                $error = 'The "read"-visibility must come before the "write"-visibility';
                $fix = $phpcsFile->addFixableError($error, $stackPtr, 'AvizKeywordOrder');
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($scopePtr + 1); $scopePtr < $stackPtr; $i++) {
                        if ($tokens[$i]['code'] !== T_WHITESPACE) {
                            break;
                        }
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                    $phpcsFile->fixer->replaceToken($scopePtr, '');
                    $phpcsFile->fixer->addContentBefore($setScopePtr, $tokens[$scopePtr]['content'] . ' ');
                    $phpcsFile->fixer->endChangeset();
                }
            }
            $firstVisibilityModifier = min($scopePtr, $setScopePtr);
        }

        if ($hasVisibilityModifier === true && $propertyInfo['is_final'] === true) {
            $scopePtr = $firstVisibilityModifier;
            $finalPtr = $phpcsFile->findPrevious(T_FINAL, ($stackPtr - 1));
            if ($finalPtr > $scopePtr) {
                $error = 'The final declaration must come before the visibility declaration';
                $fix = $phpcsFile->addFixableError($error, $stackPtr, 'FinalAfterVisibility');
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($finalPtr + 1); $finalPtr < $stackPtr; $i++) {
                        if ($tokens[$i]['code'] !== T_WHITESPACE) {
                            break;
                        }
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                    $phpcsFile->fixer->replaceToken($finalPtr, '');
                    $phpcsFile->fixer->addContentBefore($scopePtr, $tokens[$finalPtr]['content'] . ' ');
                    $phpcsFile->fixer->endChangeset();
                }
            }
        }

        if ($hasVisibilityModifier === true && $propertyInfo['is_abstract'] === true) {
            $scopePtr = $firstVisibilityModifier;
            $abstractPtr = $phpcsFile->findPrevious(T_ABSTRACT, ($stackPtr - 1));
            if ($abstractPtr > $scopePtr) {
                $error = 'The abstract declaration must come before the visibility declaration';
                $fix = $phpcsFile->addFixableError($error, $stackPtr, 'AbstractAfterVisibility');
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($abstractPtr + 1); $abstractPtr < $stackPtr; $i++) {
                        if ($tokens[$i]['code'] !== T_WHITESPACE) {
                            break;
                        }
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                    $phpcsFile->fixer->replaceToken($abstractPtr, '');
                    $phpcsFile->fixer->addContentBefore($scopePtr, $tokens[$abstractPtr]['content'] . ' ');
                    $phpcsFile->fixer->endChangeset();
                }
            }
        }

        if ($hasVisibilityModifier === true && $propertyInfo['is_static'] === true) {
            $scopePtr = $lastVisibilityModifier;
            $staticPtr = $phpcsFile->findPrevious(T_STATIC, ($stackPtr - 1));
            if ($scopePtr > $staticPtr) {
                $error = 'The static declaration must come after the visibility declaration';
                $fix = $phpcsFile->addFixableError($error, $stackPtr, 'StaticBeforeVisibility');
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($staticPtr + 1); $staticPtr < $stackPtr; $i++) {
                        if ($tokens[$i]['code'] !== T_WHITESPACE) {
                            break;
                        }
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                    $phpcsFile->fixer->replaceToken($staticPtr, '');
                    $phpcsFile->fixer->addContent($scopePtr, ' ' . $tokens[$staticPtr]['content']);
                    $phpcsFile->fixer->endChangeset();
                }
            }
        }

        if ($hasVisibilityModifier === true && $propertyInfo['is_readonly'] === true) {
            $scopePtr = $lastVisibilityModifier;
            $readonlyPtr = $phpcsFile->findPrevious(T_READONLY, ($stackPtr - 1));
            if ($scopePtr > $readonlyPtr) {
                $error = 'The readonly declaration must come after the visibility declaration';
                $fix = $phpcsFile->addFixableError($error, $stackPtr, 'ReadonlyBeforeVisibility');
                if ($fix === true) {
                    $phpcsFile->fixer->beginChangeset();
                    for ($i = ($readonlyPtr + 1); $readonlyPtr < $stackPtr; $i++) {
                        if ($tokens[$i]['code'] !== T_WHITESPACE) {
                            break;
                        }
                        $phpcsFile->fixer->replaceToken($i, '');
                    }
                    $phpcsFile->fixer->replaceToken($readonlyPtr, '');
                    $phpcsFile->fixer->addContent($scopePtr, ' ' . $tokens[$readonlyPtr]['content']);
                    $phpcsFile->fixer->endChangeset();
                }
            }
        }
    }

    /**
     * Processes normal variables.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processVariable(File $phpcsFile, int $stackPtr): void
    {
        // We don't care about normal variables.
    }

    /**
     * Processes variables in double quoted strings.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processVariableInString(File $phpcsFile, int $stackPtr): void
    {
        // We don't care about normal variables.
    }
}
