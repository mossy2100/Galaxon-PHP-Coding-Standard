<?php

/**
 * Sniff for array declaration formatting.
 *
 * Rules:
 * 1. Simple list arrays (no keys, no nested arrays): single line if possible, no trailing comma.
 * 2. List of arrays: one element per line, trailing comma required.
 * 3. Associative arrays: one key-value pair per line, arrows aligned, 4-space indent, trailing comma required.
 */

declare(strict_types=1);

namespace Galaxon\Sniffs\Arrays;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Sniffs\Sniff;
use PHP_CodeSniffer\Util\Tokens;

class ArrayDeclarationSniff implements Sniff
{
    /**
     * Tokens to ignore when looking for array content.
     *
     * @var array<int|string, int|string>
     */
    private array $ignoreTokens = [];

    /**
     * Initialize ignore tokens list.
     */
    private function initIgnoreTokens(): void
    {
        if (empty($this->ignoreTokens)) {
            $this->ignoreTokens = Tokens::$emptyTokens;
            // Also ignore comments.
            $this->ignoreTokens[T_COMMENT] = T_COMMENT;
            $this->ignoreTokens[T_DOC_COMMENT] = T_DOC_COMMENT;
            $this->ignoreTokens[T_DOC_COMMENT_OPEN_TAG] = T_DOC_COMMENT_OPEN_TAG;
            $this->ignoreTokens[T_DOC_COMMENT_CLOSE_TAG] = T_DOC_COMMENT_CLOSE_TAG;
            $this->ignoreTokens[T_DOC_COMMENT_STAR] = T_DOC_COMMENT_STAR;
            $this->ignoreTokens[T_DOC_COMMENT_STRING] = T_DOC_COMMENT_STRING;
            $this->ignoreTokens[T_DOC_COMMENT_TAG] = T_DOC_COMMENT_TAG;
            $this->ignoreTokens[T_DOC_COMMENT_WHITESPACE] = T_DOC_COMMENT_WHITESPACE;
        }
    }

    /**
     * Check if a token is a comment.
     */
    private function isComment(int|string $code): bool
    {
        return $code === T_COMMENT
            || $code === T_DOC_COMMENT
            || $code === T_DOC_COMMENT_OPEN_TAG
            || $code === T_DOC_COMMENT_CLOSE_TAG
            || $code === T_DOC_COMMENT_STAR
            || $code === T_DOC_COMMENT_STRING
            || $code === T_DOC_COMMENT_TAG
            || $code === T_DOC_COMMENT_WHITESPACE;
    }

    /**
     * Maximum line length before wrapping list arrays.
     *
     * @var int
     */
    public int $maxLineLength = 120;

    /**
     * Number of spaces to indent array elements.
     *
     * @var int
     */
    public int $indent = 4;

    /**
     * Returns an array of tokens this test wants to listen for.
     *
     * @return array<int|string>
     */
    public function register(): array
    {
        return [T_OPEN_SHORT_ARRAY, T_ARRAY];
    }

    /**
     * Processes this test, when one of its tokens is encountered.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the current token in the stack.
     */
    public function process(File $phpcsFile, int $stackPtr): void
    {
        $this->initIgnoreTokens();
        $tokens = $phpcsFile->getTokens();
        $token = $tokens[$stackPtr];

        // Get the opening and closing bracket positions.
        if ($token['code'] === T_ARRAY) {
            // Long array syntax: array(...)
            $openPtr = $phpcsFile->findNext(T_OPEN_PARENTHESIS, $stackPtr);
            if ($openPtr === false || !isset($tokens[$openPtr]['parenthesis_closer'])) {
                return;
            }
            $closePtr = $tokens[$openPtr]['parenthesis_closer'];
        } else {
            // Short array syntax: [...]
            $openPtr = $stackPtr;
            if (!isset($token['bracket_closer'])) {
                return;
            }
            $closePtr = $token['bracket_closer'];
        }

        // Skip empty arrays.
        $firstContent = $phpcsFile->findNext(T_WHITESPACE, $openPtr + 1, $closePtr, true);
        if ($firstContent === false) {
            return;
        }

        // Determine if this is an associative array.
        $isAssociative = $this->isAssociativeArray($phpcsFile, $openPtr, $closePtr);

        if ($isAssociative) {
            $this->processAssociativeArray($phpcsFile, $openPtr, $closePtr);
        } else {
            $this->processListArray($phpcsFile, $openPtr, $closePtr);
        }
    }

    /**
     * Check if the array is associative (has at least one key => value pair).
     */
    private function isAssociativeArray(File $phpcsFile, int $openPtr, int $closePtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $depth = 0;

        for ($i = $openPtr + 1; $i < $closePtr; $i++) {
            $code = $tokens[$i]['code'];

            // Track nesting depth.
            if ($code === T_OPEN_SHORT_ARRAY || $code === T_OPEN_PARENTHESIS || $code === T_OPEN_CURLY_BRACKET) {
                $depth++;
            } elseif (
                $code === T_CLOSE_SHORT_ARRAY
                || $code === T_CLOSE_PARENTHESIS
                || $code === T_CLOSE_CURLY_BRACKET
            ) {
                $depth--;
            }

            // Only check at depth 0 (top level of this array).
            if ($depth === 0 && $code === T_DOUBLE_ARROW) {
                return true;
            }
        }

        return false;
    }

    /**
     * Process a list array (no keys).
     */
    private function processListArray(File $phpcsFile, int $openPtr, int $closePtr): void
    {
        // Check if this list contains nested arrays.
        if ($this->containsNestedArrays($phpcsFile, $openPtr, $closePtr)) {
            $this->processListOfArrays($phpcsFile, $openPtr, $closePtr);
            return;
        }

        $tokens = $phpcsFile->getTokens();

        // Check for and remove trailing comma.
        $lastContent = $phpcsFile->findPrevious($this->ignoreTokens, $closePtr - 1, $openPtr, true);
        if ($lastContent !== false && $tokens[$lastContent]['code'] === T_COMMA) {
            $error = 'Simple list arrays should not have a trailing comma.';
            $fix = $phpcsFile->addFixableError($error, $lastContent, 'ListTrailingComma');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($lastContent, '');
            }
        }

        // Check if array fits on one line.
        $openLine = $tokens[$openPtr]['line'];
        $closeLine = $tokens[$closePtr]['line'];

        if ($openLine === $closeLine) {
            // Already on one line - check if it's within line length.
            $lineLength = $tokens[$closePtr]['column'] + mb_strlen($tokens[$closePtr]['content']);
            if ($lineLength <= $this->maxLineLength) {
                return; // All good.
            }
        }

        // If multi-line, check if it could fit on one line.
        $singleLineContent = $this->buildSingleLineArray($phpcsFile, $openPtr, $closePtr);
        $lineStart = $this->findLineStart($phpcsFile, $openPtr);
        $prefix = $this->getContentBefore($phpcsFile, $lineStart, $openPtr);
        $totalLength = mb_strlen($prefix) + mb_strlen($singleLineContent);

        if ($totalLength <= $this->maxLineLength && $openLine !== $closeLine) {
            // Can fit on one line but isn't - suggest fix.
            $error = 'Simple list array should be on a single line when it fits within line length.';
            $fix = $phpcsFile->addFixableError($error, $openPtr, 'ListShouldBeSingleLine');
            if ($fix === true) {
                $this->fixToSingleLine($phpcsFile, $openPtr, $closePtr, $singleLineContent);
            }
        }
    }

    /**
     * Check if the array contains nested arrays at the top level.
     */
    private function containsNestedArrays(File $phpcsFile, int $openPtr, int $closePtr): bool
    {
        $tokens = $phpcsFile->getTokens();
        $depth = 0;

        for ($i = $openPtr + 1; $i < $closePtr; $i++) {
            $code = $tokens[$i]['code'];

            // At depth 0, check for array openers.
            if ($depth === 0 && ($code === T_OPEN_SHORT_ARRAY || $code === T_ARRAY)) {
                return true;
            }

            // Track nesting depth.
            if ($code === T_OPEN_SHORT_ARRAY || $code === T_OPEN_PARENTHESIS || $code === T_OPEN_CURLY_BRACKET) {
                $depth++;
            } elseif (
                $code === T_CLOSE_SHORT_ARRAY
                || $code === T_CLOSE_PARENTHESIS
                || $code === T_CLOSE_CURLY_BRACKET
            ) {
                $depth--;
            }
        }

        return false;
    }

    /**
     * Process a list of arrays (one element per line, trailing comma).
     */
    private function processListOfArrays(File $phpcsFile, int $openPtr, int $closePtr): void
    {
        $tokens = $phpcsFile->getTokens();
        $elements = $this->getArrayElements($phpcsFile, $openPtr, $closePtr);

        if (empty($elements)) {
            return;
        }

        // Check for trailing comma - should be present.
        $lastContent = $phpcsFile->findPrevious($this->ignoreTokens, $closePtr - 1, $openPtr, true);
        if ($lastContent !== false && $tokens[$lastContent]['code'] !== T_COMMA) {
            $error = 'List of arrays should have a trailing comma.';
            $fix = $phpcsFile->addFixableError($error, $lastContent, 'ListOfArraysMissingTrailingComma');
            if ($fix === true) {
                $phpcsFile->fixer->addContent($lastContent, ',');
            }
        }

        // Check each element is on its own line.
        $baseIndent = $this->getBaseIndent($phpcsFile, $openPtr);
        $elementIndent = $baseIndent + $this->indent;
        $openLine = $tokens[$openPtr]['line'];
        $prevElementLine = $openLine;

        foreach ($elements as $index => $element) {
            $elementLine = $tokens[$element['start']]['line'];

            // First element should be on a new line after opening bracket.
            if ($index === 0 && $elementLine === $openLine) {
                $error = 'First element of list of arrays should be on a new line.';
                $fix = $phpcsFile->addFixableError($error, $element['start'], 'ListOfArraysFirstElementNewLine');
                if ($fix === true) {
                    $phpcsFile->fixer->addContentBefore($element['start'], "\n" . str_repeat(' ', $elementIndent));
                }
            } elseif ($index > 0 && $elementLine === $prevElementLine) {
                // Each subsequent element should be on its own line.
                $error = 'Each element in list of arrays should be on its own line.';
                $fix = $phpcsFile->addFixableError($error, $element['start'], 'ListOfArraysElementNewLine');
                if ($fix === true) {
                    $phpcsFile->fixer->addContentBefore($element['start'], "\n" . str_repeat(' ', $elementIndent));
                }
            }

            $prevElementLine = $elementLine;
        }

        // Check closing bracket is on its own line.
        $lastElement = end($elements);
        $lastElementLine = $tokens[$lastElement['end']]['line'];
        $closeLine = $tokens[$closePtr]['line'];

        if ($closeLine === $lastElementLine) {
            $error = 'Closing bracket of list of arrays should be on a new line.';
            $fix = $phpcsFile->addFixableError($error, $closePtr, 'ListOfArraysClosingBracketNewLine');
            if ($fix === true) {
                $phpcsFile->fixer->addContentBefore($closePtr, "\n" . str_repeat(' ', $baseIndent));
            }
        }
    }

    /**
     * Process an associative array (has keys).
     */
    private function processAssociativeArray(File $phpcsFile, int $openPtr, int $closePtr): void
    {
        $tokens = $phpcsFile->getTokens();

        // Collect all key-value pairs at the top level.
        $elements = $this->getArrayElements($phpcsFile, $openPtr, $closePtr);

        if (empty($elements)) {
            return;
        }

        // Check for trailing comma.
        $lastContent = $phpcsFile->findPrevious($this->ignoreTokens, $closePtr - 1, $openPtr, true);
        if ($lastContent !== false && $tokens[$lastContent]['code'] !== T_COMMA) {
            $error = 'Associative arrays should have a trailing comma.';
            $fix = $phpcsFile->addFixableError($error, $lastContent, 'AssocMissingTrailingComma');
            if ($fix === true) {
                $phpcsFile->fixer->addContent($lastContent, ',');
            }
        }

        // Find the maximum key length for arrow alignment.
        $maxKeyLength = 0;
        foreach ($elements as $element) {
            if ($element['arrow'] !== null) {
                $keyLength = $this->getKeyLength($phpcsFile, $element['start'], $element['arrow']);
                $maxKeyLength = max($maxKeyLength, $keyLength);
            }
        }

        // Check each element is on its own line and arrows are aligned.
        $baseIndent = $this->getBaseIndent($phpcsFile, $openPtr);
        $elementIndent = $baseIndent + $this->indent;
        $openLine = $tokens[$openPtr]['line'];

        $prevElementLine = $openLine;
        foreach ($elements as $index => $element) {
            $elementLine = $tokens[$element['start']]['line'];

            // First element should be on a new line after opening bracket.
            if ($index === 0 && $elementLine === $openLine) {
                $error = 'First element of associative array should be on a new line.';
                $fix = $phpcsFile->addFixableError($error, $element['start'], 'AssocFirstElementNewLine');
                if ($fix === true) {
                    $phpcsFile->fixer->addContentBefore($element['start'], "\n" . str_repeat(' ', $elementIndent));
                }
            } elseif ($index > 0 && $elementLine === $prevElementLine) {
                // Each subsequent element should be on its own line.
                $error = 'Each element in associative array should be on its own line.';
                $fix = $phpcsFile->addFixableError($error, $element['start'], 'AssocElementNewLine');
                if ($fix === true) {
                    $phpcsFile->fixer->addContentBefore($element['start'], "\n" . str_repeat(' ', $elementIndent));
                }
            }

            $prevElementLine = $elementLine;

            // Check arrow alignment if this element has an arrow.
            if ($element['arrow'] !== null) {
                $keyLength = $this->getKeyLength($phpcsFile, $element['start'], $element['arrow']);
                $expectedSpaces = $maxKeyLength - $keyLength + 1;

                // Check space before arrow.
                $prevToken = $phpcsFile->findPrevious(T_WHITESPACE, $element['arrow'] - 1, $element['start'], true);
                if ($prevToken !== false) {
                    $actualSpaces = $element['arrow'] - $prevToken - 1;
                    $actualSpaces = $tokens[$prevToken + 1]['code'] === T_WHITESPACE
                        ? strlen($tokens[$prevToken + 1]['content'])
                        : 0;

                    if ($actualSpaces !== $expectedSpaces) {
                        $error = 'Array arrows should be aligned; expected %s space(s) before arrow, found %s.';
                        $data = [$expectedSpaces, $actualSpaces];
                        $fix = $phpcsFile->addFixableError($error, $element['arrow'], 'AssocArrowAlignment', $data);
                        if ($fix === true) {
                            $phpcsFile->fixer->beginChangeset();
                            // Remove existing whitespace.
                            if ($tokens[$prevToken + 1]['code'] === T_WHITESPACE) {
                                $phpcsFile->fixer->replaceToken($prevToken + 1, str_repeat(' ', $expectedSpaces));
                            } else {
                                $phpcsFile->fixer->addContent($prevToken, str_repeat(' ', $expectedSpaces));
                            }
                            $phpcsFile->fixer->endChangeset();
                        }
                    }
                }
            }
        }

        // Check closing bracket is on its own line.
        $lastElement = end($elements);
        $lastElementLine = $tokens[$lastElement['end']]['line'];
        $closeLine = $tokens[$closePtr]['line'];

        if ($closeLine === $lastElementLine) {
            $error = 'Closing bracket of associative array should be on a new line.';
            $fix = $phpcsFile->addFixableError($error, $closePtr, 'AssocClosingBracketNewLine');
            if ($fix === true) {
                $phpcsFile->fixer->addContentBefore($closePtr, "\n" . str_repeat(' ', $baseIndent));
            }
        }
    }

    /**
     * Get array elements (each value or key-value pair at the top level).
     *
     * @return array<array{start: int, end: int, arrow: int|null}>
     */
    private function getArrayElements(File $phpcsFile, int $openPtr, int $closePtr): array
    {
        $tokens = $phpcsFile->getTokens();
        $elements = [];
        $depth = 0;
        $elementStart = null;
        $arrow = null;

        for ($i = $openPtr + 1; $i < $closePtr; $i++) {
            $code = $tokens[$i]['code'];

            // Skip whitespace and comments when looking for element start.
            if ($elementStart === null && ($code === T_WHITESPACE || $this->isComment($code))) {
                continue;
            }

            // Mark element start.
            if ($elementStart === null) {
                $elementStart = $i;
            }

            // Track nesting depth.
            if ($code === T_OPEN_SHORT_ARRAY || $code === T_OPEN_PARENTHESIS || $code === T_OPEN_CURLY_BRACKET) {
                $depth++;
            } elseif (
                $code === T_CLOSE_SHORT_ARRAY
                || $code === T_CLOSE_PARENTHESIS
                || $code === T_CLOSE_CURLY_BRACKET
            ) {
                $depth--;
            }

            // Track arrow at top level.
            if ($depth === 0 && $code === T_DOUBLE_ARROW) {
                $arrow = $i;
            }

            // Comma at top level ends the element.
            if ($depth === 0 && $code === T_COMMA) {
                $elements[] = [
                    'start' => $elementStart,
                    'end'   => $i - 1,
                    'arrow' => $arrow,
                ];
                $elementStart = null;
                $arrow = null;
            }
        }

        // Don't forget the last element (no trailing comma).
        if ($elementStart !== null) {
            $elements[] = [
                'start' => $elementStart,
                'end'   => $closePtr - 1,
                'arrow' => $arrow,
            ];
        }

        return $elements;
    }

    /**
     * Get the length of the key portion (before the arrow).
     */
    private function getKeyLength(File $phpcsFile, int $start, int $arrowPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        $length = 0;

        // Find the last non-whitespace token before the arrow.
        $keyEnd = $phpcsFile->findPrevious(T_WHITESPACE, $arrowPtr - 1, $start, true);
        if ($keyEnd === false) {
            $keyEnd = $arrowPtr - 1;
        }

        for ($i = $start; $i <= $keyEnd; $i++) {
            $length += mb_strlen($tokens[$i]['content']);
        }

        return $length;
    }

    /**
     * Get the base indentation of the array.
     */
    private function getBaseIndent(File $phpcsFile, int $openPtr): int
    {
        $tokens = $phpcsFile->getTokens();
        $line = $tokens[$openPtr]['line'];

        // Find the first token on this line.
        for ($i = $openPtr - 1; $i >= 0; $i--) {
            if ($tokens[$i]['line'] < $line) {
                break;
            }
        }

        $firstOnLine = $i + 1;

        // If the first token is whitespace, that's the indent.
        if ($tokens[$firstOnLine]['code'] === T_WHITESPACE) {
            return strlen($tokens[$firstOnLine]['content']);
        }

        return 0;
    }

    /**
     * Build a single-line representation of the array.
     */
    private function buildSingleLineArray(File $phpcsFile, int $openPtr, int $closePtr): string
    {
        $tokens = $phpcsFile->getTokens();
        $content = $tokens[$openPtr]['content'];
        $lastWasWhitespace = false;

        for ($i = $openPtr + 1; $i < $closePtr; $i++) {
            $tokenContent = $tokens[$i]['content'];

            if ($tokens[$i]['code'] === T_WHITESPACE) {
                if (!$lastWasWhitespace) {
                    $content .= ' ';
                    $lastWasWhitespace = true;
                }
            } else {
                // Skip trailing comma.
                if ($i === $closePtr - 1 && $tokens[$i]['code'] === T_COMMA) {
                    continue;
                }
                $content .= $tokenContent;
                $lastWasWhitespace = false;
            }
        }

        $content = rtrim($content) . $tokens[$closePtr]['content'];

        return $content;
    }

    /**
     * Find the start of the current line.
     */
    private function findLineStart(File $phpcsFile, int $ptr): int
    {
        $tokens = $phpcsFile->getTokens();
        $line = $tokens[$ptr]['line'];

        for ($i = $ptr - 1; $i >= 0; $i--) {
            if ($tokens[$i]['line'] < $line) {
                return $i + 1;
            }
        }

        return 0;
    }

    /**
     * Get the content before a position on the same line.
     */
    private function getContentBefore(File $phpcsFile, int $start, int $end): string
    {
        $tokens = $phpcsFile->getTokens();
        $content = '';

        for ($i = $start; $i < $end; $i++) {
            $content .= $tokens[$i]['content'];
        }

        return $content;
    }

    /**
     * Fix an array to be on a single line.
     */
    private function fixToSingleLine(File $phpcsFile, int $openPtr, int $closePtr, string $singleLineContent): void
    {
        $phpcsFile->fixer->beginChangeset();

        // Replace the entire array with the single-line version.
        $phpcsFile->fixer->replaceToken($openPtr, $singleLineContent);

        for ($i = $openPtr + 1; $i <= $closePtr; $i++) {
            $phpcsFile->fixer->replaceToken($i, '');
        }

        $phpcsFile->fixer->endChangeset();
    }
}
