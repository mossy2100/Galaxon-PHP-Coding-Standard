<?php

/**
 * Checks the naming of member variables.
 *
 * @author    Greg Sherwood <gsherwood@squiz.net>
 * @copyright 2006-2023 Squiz Pty Ltd (ABN 77 084 670 600)
 * @copyright 2023 PHPCSStandards and contributors
 * @license   https://github.com/PHPCSStandards/PHP_CodeSniffer/blob/HEAD/licence.txt BSD Licence
 */

namespace dev\Sniffs\NamingConventions;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\AbstractScopeSniff;
use PHP_CodeSniffer\Sniffs\AbstractVariableSniff;
use PHP_CodeSniffer\Util\Common;
use PHP_CodeSniffer\Util\Tokens;

class ValidVariableNameSniff extends AbstractVariableSniff
{
    /**
     * Only listen to variables within OO scopes.
     */
    public function __construct()
    {
        AbstractScopeSniff::__construct(Tokens::OO_SCOPE_TOKENS, [T_VARIABLE], false);
    }

    /**
     * Check if a variable in in $lowerCamelCaps format.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     * @param string $varName The variable name to inspect.
     * @param string $varType The type of variable being checked (e.g. "Variable", "Member variable").
     * @return bool If the variable name is valid.
     */
    private function checkVar(File $phpcsFile, int $stackPtr, string $varName, string $varType): bool
    {
        $result = true;

        // Forbid a leading underscore if present.
        if ($varName[0] === '_') {
            $error = '%s "$%s" must not be prefixed with an underscore.';
            $data = [$varType, $varName];
            $phpcsFile->addError($error, $stackPtr, 'LeadingUnderscore', $data);
            $result = false;
        }

        // Check the variable name is in $lowerCamelCaps format (lower-case, public, strict).
        if (Common::isCamelCaps($varName, false, true, true) === false) {
            $error = '%s "$%s" is not in valid $lowerCamelCase format.';
            $data = [$varType, $varName];
            $phpcsFile->addError($error, $stackPtr, 'MemberNotCamelCaps', $data);
            $result = false;
        }

        return $result;
    }

    /**
     * Processes class member variables.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack passed in $tokens.
     * @return void
     */
    protected function processMemberVar(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $memberName = ltrim($tokens[$stackPtr]['content'], '$');
        $this->checkVar($phpcsFile, $stackPtr, $memberName, 'Member variable');
    }

    /**
     * Processes normal variables.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processVariable(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();
        $varName = ltrim($tokens[$stackPtr]['content'], '$');

        // If it's a php reserved var, then its ok.
        if (isset(static::PHP_RESERVED_VARS[$varName]) === true) {
            return;
        }

        // Check if the variable is followed by an operator.
        $objOperator = $phpcsFile->findNext([T_WHITESPACE], ($stackPtr + 1), null, true);
        if (
            $tokens[$objOperator]['code'] === T_OBJECT_OPERATOR
            || $tokens[$objOperator]['code'] === T_NULLSAFE_OBJECT_OPERATOR
        ) {
            // Check to see if we are using a variable from an object.
            $var = $phpcsFile->findNext([T_WHITESPACE], ($objOperator + 1), null, true);
            if ($tokens[$var]['code'] === T_STRING) {
                $bracket = $phpcsFile->findNext([T_WHITESPACE], ($var + 1), null, true);
                if ($tokens[$bracket]['code'] !== T_OPEN_PARENTHESIS) {
                    $objVarName = $tokens[$var]['content'];
                    if (!$this->checkVar($phpcsFile, $stackPtr, $objVarName, 'Variable')) {
                        return;
                    }
                }
            }
        }

        $objOperator = $phpcsFile->findPrevious(T_WHITESPACE, ($stackPtr - 1), null, true);
        if ($tokens[$objOperator]['code'] === T_DOUBLE_COLON) {
            // The variable lives within a class and is referenced like MyClass::$variable, so we don't know its scope.
            if (!$this->checkVar($phpcsFile, $stackPtr, $varName, 'Variable')) {
                return;
            }
        }

        if (!$this->checkVar($phpcsFile, $stackPtr, $varName, 'Variable')) {
            return;
        }
    }

    /**
     * Processes variables in double quoted strings.
     *
     * @param File $phpcsFile The file where this token was found.
     * @param int $stackPtr The position where the token was found.
     * @return void
     */
    protected function processVariableInString(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        if (
            preg_match_all(
                '|[^\\\]\${?([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)|',
                $tokens[$stackPtr]['content'],
                $matches
            ) !== 0
        ) {
            foreach ($matches[1] as $varName) {
                // If it's a php reserved var, then its ok.
                if (isset(static::PHP_RESERVED_VARS[$varName]) === true) {
                    continue;
                }

                $this->checkVar($phpcsFile, $stackPtr, $varName, 'Interpolated variable');
            }
        }
    }
}
