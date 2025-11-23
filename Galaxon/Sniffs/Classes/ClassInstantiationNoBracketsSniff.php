<?php

/**
 * Detects unnecessary parentheses around class instantiation when accessing members.
 *
 * In PHP 8.4+, `new ClassName()->method()` is valid syntax, so wrapping in
 * parentheses like `(new ClassName())->method()` is no longer necessary.
 */

declare(strict_types=1);

namespace Galaxon\Sniffs\Classes;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class ClassInstantiationNoBracketsSniff implements Sniff
{
    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register()
    {
        return [T_OPEN_PARENTHESIS];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int  $stackPtr  The position of the current token in the stack passed in $tokens.
     * @return void
     */
    public function process(File $phpcsFile, int $stackPtr)
    {
        $tokens = $phpcsFile->getTokens();

        // Check if the next non-whitespace token is T_NEW.
        $nextNonEmpty = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), null, true);
        if ($nextNonEmpty === false || $tokens[$nextNonEmpty]['code'] !== T_NEW) {
            return;
        }

        // Make sure this parenthesis has a matching closer.
        if (!isset($tokens[$stackPtr]['parenthesis_closer'])) {
            return;
        }

        $closeParenPtr = $tokens[$stackPtr]['parenthesis_closer'];

        // Check if the token after the closing parenthesis is an object operator.
        $afterClose = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($closeParenPtr + 1), null, true);
        if ($afterClose === false) {
            return;
        }

        if (
            $tokens[$afterClose]['code'] !== T_OBJECT_OPERATOR
            && $tokens[$afterClose]['code'] !== T_NULLSAFE_OBJECT_OPERATOR
        ) {
            return;
        }

        // Check this isn't already inside another expression that needs the parentheses.
        // For example: `($condition ? new Foo() : new Bar())->method()` needs them.
        // We check if there's anything between the open paren and T_NEW other than whitespace.
        $betweenOpen = $phpcsFile->findNext(Tokens::EMPTY_TOKENS, ($stackPtr + 1), $nextNonEmpty, true);
        if ($betweenOpen !== false) {
            return;
        }

        // Found the pattern: (new ClassName(...))->something
        $error = 'Unnecessary parentheses around class instantiation; '
               . 'use "new ClassName()->member" instead of "(new ClassName())->member" (PHP 8.4+)';
        $fix = $phpcsFile->addFixableError($error, $stackPtr, 'UnnecessaryParentheses');

        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($stackPtr, '');
            $phpcsFile->fixer->replaceToken($closeParenPtr, '');
            $phpcsFile->fixer->endChangeset();
        }
    }
}
