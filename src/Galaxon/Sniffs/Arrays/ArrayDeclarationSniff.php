<?php

/**
 * Sniff for array declaration formatting.
 *
 * Rules:
 * 1. Simple list arrays (no keys, no nested arrays): single line if possible, no trailing comma.
 * 2. List arrays too long for one line: grid format with uniform padding, trailing comma on all items.
 * 3. List arrays where grid format doesn't fit (items too wide): one element per line, trailing comma required.
 * 4. List of arrays: one element per line, trailing comma required.
 * 5. Dictionaries: one key-value pair per line, arrows aligned, 4-space indent, trailing comma required.
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

        // Determine if this is a dictionary.
        $isDictionary = $this->isDictionary($phpcsFile, $openPtr, $closePtr);

        if ($isDictionary) {
            $this->processDictionary($phpcsFile, $openPtr, $closePtr);
        } else {
            $this->processList($phpcsFile, $openPtr, $closePtr);
        }
    }

    /**
     * Check if the array is a dictionary (has at least one key => value pair).
     */
    private function isDictionary(File $phpcsFile, int $openPtr, int $closePtr): bool
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
    private function processList(File $phpcsFile, int $openPtr, int $closePtr): void
    {
        // Check if this list contains nested arrays.
        if ($this->containsNestedArrays($phpcsFile, $openPtr, $closePtr)) {
            $this->processListOfArrays($phpcsFile, $openPtr, $closePtr);
            return;
        }

        $tokens = $phpcsFile->getTokens();

        // Build single-line content and measure total length.
        $singleLineContent = $this->buildSingleLineArray($phpcsFile, $openPtr, $closePtr);
        $lineStart = $this->findLineStart($phpcsFile, $openPtr);
        $prefix = $this->getContentBefore($phpcsFile, $lineStart, $openPtr);
        $totalLength = mb_strlen($prefix) + mb_strlen($singleLineContent);

        $openLine = $tokens[$openPtr]['line'];
        $closeLine = $tokens[$closePtr]['line'];

        $elements = $this->getArrayElements($phpcsFile, $openPtr, $closePtr);
        if (empty($elements)) {
            return;
        }

        // Target: single line (lists that fit within line length and have no multiline elements).
        $hasMultilineElements = $this->hasMultilineElements($phpcsFile, $elements);
        if ($totalLength <= $this->maxLineLength && !$hasMultilineElements) {
            if ($openLine === $closeLine) {
                // Already single-line — just check trailing comma.
                $this->checkListTrailingComma($phpcsFile, $openPtr, $closePtr, false);
                return;
            }

            // Multi-line but fits on one line — fix it (buildSingleLineArray already strips trailing comma).
            $error = 'Simple list array should be on a single line when it fits within line length.';
            $fix = $phpcsFile->addFixableError($error, $openPtr, 'ListShouldBeSingleLine');
            if ($fix === true) {
                $this->fixToSingleLine($phpcsFile, $openPtr, $closePtr, $singleLineContent);
            }
            return;
        }

        // Too long for single line — determine multiline format.
        $baseIndent = $this->getBaseIndent($phpcsFile, $openPtr);
        $gridEligible = $this->isGridEligible($phpcsFile, $elements);

        // Lists containing function/method calls, new expressions, or closures always go one per line.
        if (!$gridEligible) {
            $this->processOnePerLineArray($phpcsFile, $openPtr, $closePtr, $elements, $baseIndent);
            return;
        }

        // Try grid format.
        $elementIndentSpaces = $baseIndent + $this->indent;
        $maxValueWidth = $this->getMaxElementWidth($phpcsFile, $elements);
        $itemsPerLine = (int)floor(($this->maxLineLength + 1 - $elementIndentSpaces) / ($maxValueWidth + 2));

        if ($itemsPerLine > 1) {
            // Target: grid format.
            $this->processGridArray(
                $phpcsFile,
                $openPtr,
                $closePtr,
                $elements,
                $baseIndent,
                $maxValueWidth,
                $itemsPerLine
            );
            return;
        }

        // Target: one per line.
        $this->processOnePerLineArray($phpcsFile, $openPtr, $closePtr, $elements, $baseIndent);
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
                    $this->fixNewLineBefore($phpcsFile, $openPtr, $element['start'], $elementIndent);
                }
            } elseif ($index > 0 && $elementLine === $prevElementLine) {
                // Each subsequent element should be on its own line.
                $error = 'Each element in list of arrays should be on its own line.';
                $fix = $phpcsFile->addFixableError($error, $element['start'], 'ListOfArraysElementNewLine');
                if ($fix === true) {
                    $this->fixNewLineBefore($phpcsFile, $openPtr, $element['start'], $elementIndent);
                }
            } else {
                // Element is on its own line - check indentation.
                $this->checkIndent($phpcsFile, $element['start'], $elementIndent, 'ListOfArraysElementIndent');
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
                $this->fixNewLineBefore($phpcsFile, $openPtr, $closePtr, $baseIndent);
            }
        } else {
            $this->checkIndent($phpcsFile, $closePtr, $baseIndent, 'ListOfArraysClosingBracketIndent');
        }
    }

    /**
     * Process a dictionary (has keys).
     */
    private function processDictionary(File $phpcsFile, int $openPtr, int $closePtr): void
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
            $error = 'Dictionaries should have a trailing comma.';
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
                $error = 'First element of dictionary should be on a new line.';
                $fix = $phpcsFile->addFixableError($error, $element['start'], 'AssocFirstElementNewLine');
                if ($fix === true) {
                    $this->fixNewLineBefore($phpcsFile, $openPtr, $element['start'], $elementIndent);
                }
            } elseif ($index > 0 && $elementLine === $prevElementLine) {
                // Each subsequent element should be on its own line.
                $error = 'Each element in dictionary should be on its own line.';
                $fix = $phpcsFile->addFixableError($error, $element['start'], 'AssocElementNewLine');
                if ($fix === true) {
                    $this->fixNewLineBefore($phpcsFile, $openPtr, $element['start'], $elementIndent);
                }
            } else {
                // Element is on its own line - check indentation.
                $this->checkIndent($phpcsFile, $element['start'], $elementIndent, 'AssocElementIndent');
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

                // Check that the value starts on the same line as the arrow.
                $valueStart = $phpcsFile->findNext(T_WHITESPACE, $element['arrow'] + 1, null, true);
                if ($valueStart !== false && $tokens[$valueStart]['line'] !== $tokens[$element['arrow']]['line']) {
                    $error = 'Array value should be on the same line as the double arrow.';
                    $fix = $phpcsFile->addFixableError($error, $valueStart, 'AssocValueNotOnArrowLine');
                    if ($fix === true) {
                        $phpcsFile->fixer->beginChangeset();
                        // Remove all tokens between the arrow and the value.
                        for ($j = $element['arrow'] + 1; $j < $valueStart; $j++) {
                            $phpcsFile->fixer->replaceToken($j, '');
                        }
                        // Add a single space after the arrow.
                        $phpcsFile->fixer->addContent($element['arrow'], ' ');
                        $phpcsFile->fixer->endChangeset();
                    }
                }
            }
        }

        // Check closing bracket is on its own line.
        $lastElement = end($elements);
        $lastElementLine = $tokens[$lastElement['end']]['line'];
        $closeLine = $tokens[$closePtr]['line'];

        if ($closeLine === $lastElementLine) {
            $error = 'Closing bracket of dictionary should be on a new line.';
            $fix = $phpcsFile->addFixableError($error, $closePtr, 'AssocClosingBracketNewLine');
            if ($fix === true) {
                $this->fixNewLineBefore($phpcsFile, $openPtr, $closePtr, $baseIndent);
            }
        } else {
            $this->checkIndent($phpcsFile, $closePtr, $baseIndent, 'AssocClosingBracketIndent');
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
        $lastWasWhitespace = true;

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
     * Check that a token has the expected indentation, and fix if not.
     */
    private function checkIndent(File $phpcsFile, int $tokenPtr, int $expectedIndent, string $errorCode): void
    {
        $tokens = $phpcsFile->getTokens();
        $actualIndent = $tokens[$tokenPtr]['column'] - 1;

        if ($actualIndent === $expectedIndent) {
            return;
        }

        $error = 'Array element indented incorrectly; expected %s space(s), found %s.';
        $data = [$expectedIndent, $actualIndent];
        $fix = $phpcsFile->addFixableError($error, $tokenPtr, $errorCode, $data);
        if ($fix === true) {
            $padding = str_repeat(' ', $expectedIndent);
            if ($tokens[$tokenPtr]['column'] === 1) {
                // Token is at column 1; prepend the padding to the token content.
                $phpcsFile->fixer->replaceToken($tokenPtr, $padding . $tokens[$tokenPtr]['content']);
            } else {
                // Replace the whitespace token before this token.
                $phpcsFile->fixer->replaceToken($tokenPtr - 1, $padding);
            }
        }
    }

    /**
     * Fix a newline before a token by replacing whitespace between the boundary and the target.
     *
     * This uses a changeset to remove existing whitespace tokens between the
     * previous non-whitespace token and the target, then inserts newline + indent.
     * This prevents the addContentBefore looping issue where content is prepended
     * on every fixer pass without removing the old whitespace.
     */
    private function fixNewLineBefore(File $phpcsFile, int $afterPtr, int $targetPtr, int $indent): void
    {
        $tokens = $phpcsFile->getTokens();
        $phpcsFile->fixer->beginChangeset();

        // Find the previous non-whitespace token before the target.
        $prev = $phpcsFile->findPrevious(T_WHITESPACE, $targetPtr - 1, $afterPtr, true);
        if ($prev === false) {
            $prev = $afterPtr;
        }

        // Remove all whitespace tokens between the previous token and the target.
        for ($i = $prev + 1; $i < $targetPtr; $i++) {
            if ($tokens[$i]['code'] === T_WHITESPACE) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
        }

        // Add newline + indent after the previous token.
        $phpcsFile->fixer->addContent($prev, "\n" . str_repeat(' ', $indent));

        $phpcsFile->fixer->endChangeset();
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

    /**
     * Check if all elements are eligible for grid formatting.
     *
     * Grid format is suitable for simple expressions: scalars, variables, property accesses, constants, and simple
     * arithmetic. Elements containing function/method calls, closures, arrow functions, or object construction are
     * excluded because they are too visually complex for grid padding.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<array{start: int, end: int, arrow: int|null}> $elements The array elements.
     * @return bool True if all elements are eligible for grid formatting.
     */
    private function isGridEligible(File $phpcsFile, array $elements): bool
    {
        $tokens = $phpcsFile->getTokens();

        // Token codes that, when followed by T_OPEN_PARENTHESIS, indicate a call.
        $callPrecedingTokens = [
            T_STRING   => true,
            T_VARIABLE => true,
            T_CLOSURE  => true,
            T_FN       => true,
        ];

        foreach ($elements as $element) {
            for ($i = $element['start']; $i <= $element['end']; $i++) {
                $code = $tokens[$i]['code'];

                // New expressions and nested arrays are never grid-eligible.
                if ($code === T_NEW || $code === T_OPEN_SHORT_ARRAY || $code === T_ARRAY) {
                    return false;
                }

                // Check if an open parenthesis is a function/method call.
                if ($code === T_OPEN_PARENTHESIS) {
                    $prev = $phpcsFile->findPrevious(T_WHITESPACE, $i - 1, $element['start'], true);
                    if ($prev !== false && isset($callPrecedingTokens[$tokens[$prev]['code']])) {
                        return false;
                    }
                }
            }
        }

        return true;
    }

    /**
     * Check if any array element spans multiple lines.
     *
     * @param File $phpcsFile The file being scanned.
     * @param list<array{start: int, end: int, arrow: int|null}> $elements The elements.
     * @return bool True if any element spans more than one line.
     */
    private function hasMultilineElements(File $phpcsFile, array $elements): bool
    {
        $tokens = $phpcsFile->getTokens();

        foreach ($elements as $element) {
            if ($tokens[$element['start']]['line'] !== $tokens[$element['end']]['line']) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get the text content of an array element with normalized whitespace.
     *
     * Collapses all whitespace (including newlines) to single spaces and trims
     * leading/trailing whitespace.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array{start: int, end: int, arrow: int|null} $element The element.
     * @return string The content with normalized whitespace.
     */
    private function getElementContent(File $phpcsFile, array $element): string
    {
        $tokens = $phpcsFile->getTokens();
        $content = '';
        $lastWasWhitespace = true;
        for ($i = $element['start']; $i <= $element['end']; $i++) {
            if ($tokens[$i]['code'] === T_WHITESPACE) {
                if (!$lastWasWhitespace) {
                    $content .= ' ';
                    $lastWasWhitespace = true;
                }
            } else {
                $content .= $tokens[$i]['content'];
                $lastWasWhitespace = false;
            }
        }
        return rtrim($content);
    }

    /**
     * Get the maximum text width across all array elements.
     *
     * @param File $phpcsFile The file being scanned.
     * @param array<array{start: int, end: int, arrow: int|null}> $elements The array elements.
     * @return int The maximum element width in characters.
     */
    private function getMaxElementWidth(File $phpcsFile, array $elements): int
    {
        $maxWidth = 0;
        foreach ($elements as $element) {
            $width = mb_strlen($this->getElementContent($phpcsFile, $element));
            if ($width > $maxWidth) {
                $maxWidth = $width;
            }
        }
        return $maxWidth;
    }

    /**
     * Build the expected grid-formatted array string.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr The opening bracket token position.
     * @param int $closePtr The closing bracket token position.
     * @param array<array{start: int, end: int, arrow: int|null}> $elements The array elements.
     * @param int $baseIndent The base indentation in spaces.
     * @param int $maxValueWidth The maximum element width.
     * @param int $itemsPerLine The number of items per grid line.
     * @return string The expected grid-formatted array.
     */
    private function buildGridArray(
        File $phpcsFile,
        int $openPtr,
        int $closePtr,
        array $elements,
        int $baseIndent,
        int $maxValueWidth,
        int $itemsPerLine
    ): string {
        $tokens = $phpcsFile->getTokens();
        $elementIndent = str_repeat(' ', $baseIndent + $this->indent);
        $bracketIndent = str_repeat(' ', $baseIndent);
        $nElements = count($elements);

        $grid = $tokens[$openPtr]['content'] . "\n";
        $itemCountThisLine = 0;

        foreach ($elements as $i => $element) {
            $value = $this->getElementContent($phpcsFile, $element);

            // Start of line — add indent.
            if ($itemCountThisLine === 0) {
                $grid .= $elementIndent;
            }

            $itemCountThisLine++;
            $isLastOnRow = ($itemCountThisLine === $itemsPerLine || $i === $nElements - 1);

            if ($isLastOnRow) {
                // Last item on row: value + comma, no padding (avoids trailing whitespace).
                $grid .= $value . ",\n";
                $itemCountThisLine = 0;
            } else {
                // Non-last item on row: pad value+comma to uniform width, then space.
                $grid .= mb_str_pad($value . ',', $maxValueWidth + 1) . ' ';
            }
        }

        $grid .= $bracketIndent . $tokens[$closePtr]['content'];
        return $grid;
    }

    /**
     * Process a list array that should be in grid format.
     *
     * Compares the actual array content against the expected grid format and
     * reports a fixable error if they differ. Uses wholesale replacement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr The opening bracket token position.
     * @param int $closePtr The closing bracket token position.
     * @param array<array{start: int, end: int, arrow: int|null}> $elements The array elements.
     * @param int $baseIndent The base indentation in spaces.
     * @param int $maxValueWidth The maximum element width.
     * @param int $itemsPerLine The number of items per grid line.
     */
    private function processGridArray(
        File $phpcsFile,
        int $openPtr,
        int $closePtr,
        array $elements,
        int $baseIndent,
        int $maxValueWidth,
        int $itemsPerLine
    ): void {
        // Build expected grid format.
        $expected = $this->buildGridArray(
            $phpcsFile,
            $openPtr,
            $closePtr,
            $elements,
            $baseIndent,
            $maxValueWidth,
            $itemsPerLine
        );

        // Build actual content.
        $tokens = $phpcsFile->getTokens();
        $actual = '';
        for ($i = $openPtr; $i <= $closePtr; $i++) {
            $actual .= $tokens[$i]['content'];
        }

        if ($actual === $expected) {
            return;
        }

        $error = 'List array should use grid format.';
        $fix = $phpcsFile->addFixableError($error, $openPtr, 'ListShouldBeGrid');
        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($openPtr, $expected);
            for ($i = $openPtr + 1; $i <= $closePtr; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Build the expected one-per-line array string.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr The opening bracket token position.
     * @param int $closePtr The closing bracket token position.
     * @param array<array{start: int, end: int, arrow: int|null}> $elements The array elements.
     * @param int $baseIndent The base indentation in spaces.
     * @return string The expected one-per-line array.
     */
    private function buildOnePerLineArray(
        File $phpcsFile,
        int $openPtr,
        int $closePtr,
        array $elements,
        int $baseIndent
    ): string {
        $tokens = $phpcsFile->getTokens();
        $elementIndent = str_repeat(' ', $baseIndent + $this->indent);
        $bracketIndent = str_repeat(' ', $baseIndent);

        $result = $tokens[$openPtr]['content'] . "\n";

        foreach ($elements as $element) {
            $value = $this->getElementContent($phpcsFile, $element);
            $result .= $elementIndent . $value . ",\n";
        }

        $result .= $bracketIndent . $tokens[$closePtr]['content'];
        return $result;
    }

    /**
     * Process a list array that should be one element per line.
     *
     * Compares the actual array content against the expected one-per-line format
     * and reports a fixable error if they differ. Uses wholesale replacement.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr The opening bracket token position.
     * @param int $closePtr The closing bracket token position.
     * @param array<array{start: int, end: int, arrow: int|null}> $elements The array elements.
     * @param int $baseIndent The base indentation in spaces.
     */
    private function processOnePerLineArray(
        File $phpcsFile,
        int $openPtr,
        int $closePtr,
        array $elements,
        int $baseIndent
    ): void {
        // Build expected one-per-line format.
        $expected = $this->buildOnePerLineArray($phpcsFile, $openPtr, $closePtr, $elements, $baseIndent);

        // Build actual content.
        $tokens = $phpcsFile->getTokens();
        $actual = '';
        for ($i = $openPtr; $i <= $closePtr; $i++) {
            $actual .= $tokens[$i]['content'];
        }

        if ($actual === $expected) {
            return;
        }

        $error = 'List array should use one element per line format.';
        $fix = $phpcsFile->addFixableError($error, $openPtr, 'ListShouldBeOnePerLine');
        if ($fix === true) {
            $phpcsFile->fixer->beginChangeset();
            $phpcsFile->fixer->replaceToken($openPtr, $expected);
            for ($i = $openPtr + 1; $i <= $closePtr; $i++) {
                $phpcsFile->fixer->replaceToken($i, '');
            }
            $phpcsFile->fixer->endChangeset();
        }
    }

    /**
     * Check and fix trailing comma in a list array.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $openPtr The opening bracket token position.
     * @param int $closePtr The closing bracket token position.
     * @param bool $required Whether a trailing comma is required (true) or forbidden (false).
     */
    private function checkListTrailingComma(File $phpcsFile, int $openPtr, int $closePtr, bool $required): void
    {
        $tokens = $phpcsFile->getTokens();
        $lastContent = $phpcsFile->findPrevious($this->ignoreTokens, $closePtr - 1, $openPtr, true);

        if ($lastContent === false) {
            return;
        }

        $hasTrailingComma = ($tokens[$lastContent]['code'] === T_COMMA);

        if ($required && !$hasTrailingComma) {
            $error = 'List array should have a trailing comma.';
            $fix = $phpcsFile->addFixableError($error, $lastContent, 'ListMissingTrailingComma');
            if ($fix === true) {
                $phpcsFile->fixer->addContent($lastContent, ',');
            }
        } elseif (!$required && $hasTrailingComma) {
            $error = 'Simple list arrays should not have a trailing comma.';
            $fix = $phpcsFile->addFixableError($error, $lastContent, 'ListTrailingComma');
            if ($fix === true) {
                $phpcsFile->fixer->replaceToken($lastContent, '');
            }
        }
    }
}
