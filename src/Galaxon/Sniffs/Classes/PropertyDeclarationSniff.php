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

        $this->checkUnderscore($phpcsFile, $stackPtr, $tokens);
        $this->checkMultipleDeclarations($phpcsFile, $stackPtr, $tokens);
        $this->checkTypeSpacing($phpcsFile, $stackPtr, $tokens, $propertyInfo);
        $this->checkVisibility($phpcsFile, $stackPtr, $tokens, $propertyInfo);
        $this->checkModifierOrder($phpcsFile, $stackPtr, $tokens, $propertyInfo);
    }

    /**
     * Check for underscore-prefixed property names.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     */
    private function checkUnderscore(File $phpcsFile, int $stackPtr, array $tokens): void
    {
        $content = $tokens[$stackPtr]['content'];
        assert(is_string($content));
        if (isset($content[1]) && $content[1] === '_') {
            $error = 'Property name "%s" should not be prefixed with an underscore to indicate visibility';
            $data = [$tokens[$stackPtr]['content']];
            $phpcsFile->addWarning($error, $stackPtr, 'Underscore', $data);
        }
    }

    /**
     * Check for multiple property declarations on one line.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     */
    private function checkMultipleDeclarations(File $phpcsFile, int $stackPtr, array $tokens): void
    {
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
        $next = $phpcsFile->findNext([T_VARIABLE, T_SEMICOLON, T_OPEN_CURLY_BRACKET], ($stackPtr + 1));
        if ($next !== false && $tokens[$next]['code'] === T_VARIABLE) {
            // Check it's not inside a default value (e.g. public array $foo = [$bar]).
            $equals = $phpcsFile->findNext(T_EQUAL, ($stackPtr + 1), $next);
            if ($equals === false) {
                $error = 'There must not be more than one property declared per statement';
                $phpcsFile->addError($error, $stackPtr, 'Multiple');
            }
        }
    }

    /**
     * Check spacing after the property type declaration.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     * @param array<string, mixed> $propertyInfo The property info from getMemberProperties().
     */
    private function checkTypeSpacing(File $phpcsFile, int $stackPtr, array $tokens, array $propertyInfo): void
    {
        if ($propertyInfo['type'] === '') {
            return;
        }

        /** @var int $typeToken */
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
            $found = $tokens[$next]['line'] !== $tokens[$typeToken]['line']
                ? 'newline'
                : $tokens[($typeToken + 1)]['length'];

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

    /**
     * Check that visibility is declared.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     * @param array<string, mixed> $propertyInfo The property info from getMemberProperties().
     */
    private function checkVisibility(File $phpcsFile, int $stackPtr, array $tokens, array $propertyInfo): void
    {
        if ($propertyInfo['scope_specified'] === false && $propertyInfo['set_scope'] === false) {
            $error = 'Visibility must be declared on property "%s"';
            $data = [$tokens[$stackPtr]['content']];
            $phpcsFile->addError($error, $stackPtr, 'ScopeMissing', $data);
        }
    }

    /**
     * Check modifier ordering (visibility, final, abstract, static, readonly, asymmetric visibility).
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the property token.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     * @param array<string, mixed> $propertyInfo The property info from getMemberProperties().
     */
    private function checkModifierOrder(File $phpcsFile, int $stackPtr, array $tokens, array $propertyInfo): void
    {
        $hasVisibility = ($propertyInfo['scope_specified'] === true || $propertyInfo['set_scope'] !== false);
        if (!$hasVisibility) {
            return;
        }

        $lastVisibilityPtr = $phpcsFile->findPrevious(Tokens::SCOPE_MODIFIERS, ($stackPtr - 1));
        assert(is_int($lastVisibilityPtr));
        $firstVisibilityPtr = $lastVisibilityPtr;

        // Check asymmetric visibility ordering (read-visibility before write-visibility).
        if ($propertyInfo['scope_specified'] === true && $propertyInfo['set_scope'] !== false) {
            $scopePtr = $phpcsFile->findPrevious([T_PUBLIC, T_PROTECTED, T_PRIVATE], ($stackPtr - 1));
            $setScopePtr = $phpcsFile->findPrevious([T_PUBLIC_SET, T_PROTECTED_SET, T_PRIVATE_SET], ($stackPtr - 1));
            assert(is_int($scopePtr) && is_int($setScopePtr));
            if ($scopePtr > $setScopePtr) {
                $fix = $phpcsFile->addFixableError(
                    'The "read"-visibility must come before the "write"-visibility',
                    $stackPtr,
                    'AvizKeywordOrder'
                );
                if ($fix === true) {
                    $this->moveModifierBefore($phpcsFile, $tokens, $stackPtr, $scopePtr, $setScopePtr);
                }
            }
            $firstVisibilityPtr = min($scopePtr, $setScopePtr);
        }

        // Check final comes before visibility.
        if ($propertyInfo['is_final'] === true) {
            $finalPtr = $phpcsFile->findPrevious(T_FINAL, ($stackPtr - 1));
            assert(is_int($finalPtr));
            if ($finalPtr > $firstVisibilityPtr) {
                $fix = $phpcsFile->addFixableError(
                    'The final declaration must come before the visibility declaration',
                    $stackPtr,
                    'FinalAfterVisibility'
                );
                if ($fix === true) {
                    $this->moveModifierBefore($phpcsFile, $tokens, $stackPtr, $finalPtr, $firstVisibilityPtr);
                }
            }
        }

        // Check abstract comes before visibility.
        if ($propertyInfo['is_abstract'] === true) {
            $abstractPtr = $phpcsFile->findPrevious(T_ABSTRACT, ($stackPtr - 1));
            assert(is_int($abstractPtr));
            if ($abstractPtr > $firstVisibilityPtr) {
                $fix = $phpcsFile->addFixableError(
                    'The abstract declaration must come before the visibility declaration',
                    $stackPtr,
                    'AbstractAfterVisibility'
                );
                if ($fix === true) {
                    $this->moveModifierBefore($phpcsFile, $tokens, $stackPtr, $abstractPtr, $firstVisibilityPtr);
                }
            }
        }

        // Check static comes after visibility.
        if ($propertyInfo['is_static'] === true) {
            $staticPtr = $phpcsFile->findPrevious(T_STATIC, ($stackPtr - 1));
            assert(is_int($staticPtr));
            if ($lastVisibilityPtr > $staticPtr) {
                $fix = $phpcsFile->addFixableError(
                    'The static declaration must come after the visibility declaration',
                    $stackPtr,
                    'StaticBeforeVisibility'
                );
                if ($fix === true) {
                    $this->moveModifierAfter($phpcsFile, $tokens, $stackPtr, $staticPtr, $lastVisibilityPtr);
                }
            }
        }

        // Check readonly comes after visibility.
        if ($propertyInfo['is_readonly'] === true) {
            $readonlyPtr = $phpcsFile->findPrevious(T_READONLY, ($stackPtr - 1));
            assert(is_int($readonlyPtr));
            if ($lastVisibilityPtr > $readonlyPtr) {
                $fix = $phpcsFile->addFixableError(
                    'The readonly declaration must come after the visibility declaration',
                    $stackPtr,
                    'ReadonlyBeforeVisibility'
                );
                if ($fix === true) {
                    $this->moveModifierAfter($phpcsFile, $tokens, $stackPtr, $readonlyPtr, $lastVisibilityPtr);
                }
            }
        }
    }

    /**
     * Move a modifier keyword to before a target position.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     * @param int $stackPtr The property token position.
     * @param int $modifierPtr The modifier to move.
     * @param int $targetPtr The position to move it before.
     */
    private function moveModifierBefore(
        File $phpcsFile,
        array $tokens,
        int $stackPtr,
        int $modifierPtr,
        int $targetPtr
    ): void {
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($modifierPtr + 1); $modifierPtr < $stackPtr; $i++) {
            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                break;
            }
            $phpcsFile->fixer->replaceToken($i, '');
        }
        $content = $tokens[$modifierPtr]['content'];
        assert(is_string($content));
        $phpcsFile->fixer->replaceToken($modifierPtr, '');
        $phpcsFile->fixer->addContentBefore($targetPtr, $content . ' ');
        $phpcsFile->fixer->endChangeset();
    }

    /**
     * Move a modifier keyword to after a target position.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<int, array<string, mixed>> $tokens The token stack.
     * @param int $stackPtr The property token position.
     * @param int $modifierPtr The modifier to move.
     * @param int $targetPtr The position to move it after.
     */
    private function moveModifierAfter(
        File $phpcsFile,
        array $tokens,
        int $stackPtr,
        int $modifierPtr,
        int $targetPtr
    ): void {
        $content = $tokens[$modifierPtr]['content'];
        assert(is_string($content));
        $phpcsFile->fixer->beginChangeset();
        for ($i = ($modifierPtr + 1); $modifierPtr < $stackPtr; $i++) {
            if ($tokens[$i]['code'] !== T_WHITESPACE) {
                break;
            }
            $phpcsFile->fixer->replaceToken($i, '');
        }
        $phpcsFile->fixer->replaceToken($modifierPtr, '');
        $phpcsFile->fixer->addContent($targetPtr, ' ' . $content);
        $phpcsFile->fixer->endChangeset();
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
