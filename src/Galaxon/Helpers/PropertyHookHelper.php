<?php

declare(strict_types=1);

namespace Galaxon\Helpers;

use PHP_CodeSniffer\Files\File;
use PHP_CodeSniffer\Util\Tokens;

/**
 * Helper class for detecting PHP 8.4 property hooks.
 *
 * Property hooks have the syntax:
 *   public Type $propertyName {
 *       get { ... }
 *       set { ... }
 *   }
 *
 * Or with arrow functions:
 *   public Type $propertyName {
 *       get => expression;
 *       set => expression;
 *   }
 */
class PropertyHookHelper
{
    /**
     * Check if a token is inside a property hook body.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the token to check.
     * @return bool True if the token is inside a property hook body.
     */
    public static function isInsidePropertyHook(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        // Must be inside a class/trait/interface.
        if (empty($tokens[$stackPtr]['conditions'])) {
            return false;
        }

        // Check each condition (scope) the token is in.
        foreach ($tokens[$stackPtr]['conditions'] as $conditionPtr => $conditionCode) {
            // Skip if not an OO scope.
            if (!isset(Tokens::OO_SCOPE_TOKENS[$conditionCode])) {
                continue;
            }

            // Found an OO scope. Now check if there's a property hook between
            // the OO scope and the current token.
            $propertyHookOpener = self::findPropertyHookOpener($phpcsFile, $conditionPtr, $stackPtr);
            if ($propertyHookOpener !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Find the property hook opener (if any) that contains the given token.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $classPtr The position of the class/trait/interface.
     * @param int $stackPtr The position of the token to check.
     * @return int|false The position of the property hook opener, or false if not in a property hook.
     */
    public static function findPropertyHookOpener(File $phpcsFile, int $classPtr, int $stackPtr): int|false
    {
        $tokens = $phpcsFile->getTokens();

        // Get the class scope boundaries.
        if (!isset($tokens[$classPtr]['scope_opener'], $tokens[$classPtr]['scope_closer'])) {
            return false;
        }

        $classOpener = $tokens[$classPtr]['scope_opener'];
        $classCloser = $tokens[$classPtr]['scope_closer'];

        // The token must be within the class scope.
        if ($stackPtr <= $classOpener || $stackPtr >= $classCloser) {
            return false;
        }

        // Search backwards from stackPtr to find an opening brace that might be a property hook.
        $depth = 0;
        for ($i = $stackPtr; $i > $classOpener; $i--) {
            if ($tokens[$i]['code'] === T_CLOSE_CURLY_BRACKET) {
                $depth++;
            } elseif ($tokens[$i]['code'] === T_OPEN_CURLY_BRACKET) {
                if ($depth > 0) {
                    $depth--;
                } else {
                    // Found an opening brace at our nesting level.
                    // Check if it's a property hook opener.
                    if (self::isPropertyHookOpener($phpcsFile, $i)) {
                        return $i;
                    }
                }
            }
        }

        return false;
    }

    /**
     * Check if an opening curly brace is a property hook opener.
     *
     * A property hook opener is a `{` that comes after a property variable,
     * optionally with a default value assignment.
     *
     * Pattern: `public Type $name { get ... }` or `public Type $name = value { get ... }`
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $bracePtr The position of the opening curly brace.
     * @return bool True if the brace is a property hook opener.
     */
    public static function isPropertyHookOpener(File $phpcsFile, int $bracePtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$bracePtr]['code'] !== T_OPEN_CURLY_BRACKET) {
            return false;
        }

        // Search backwards for a T_VARIABLE, skipping whitespace, comments,
        // and optionally a default value assignment.
        $prev = $bracePtr - 1;

        // Skip whitespace and comments.
        while ($prev > 0 && isset(Tokens::EMPTY_TOKENS[$tokens[$prev]['code']])) {
            $prev--;
        }

        // If there's a default value, skip backwards past the = sign and value.
        // We need to handle complex default values like arrays, etc.
        if ($prev > 0) {
            // Check if we're at the end of a default value assignment.
            // This could be a simple value, or a complex expression.
            $possibleValueEnd = $prev;

            // Try to find an equals sign by skipping backwards.
            $searchPtr = $possibleValueEnd;
            $parenDepth = 0;
            $bracketDepth = 0;
            $braceDepth = 0;

            while ($searchPtr > 0) {
                $code = $tokens[$searchPtr]['code'];

                // Track nested structures.
                if ($code === T_CLOSE_PARENTHESIS) {
                    $parenDepth++;
                } elseif ($code === T_OPEN_PARENTHESIS) {
                    if ($parenDepth > 0) {
                        $parenDepth--;
                    } else {
                        break; // Unmatched open paren - stop.
                    }
                } elseif ($code === T_CLOSE_SQUARE_BRACKET) {
                    $bracketDepth++;
                } elseif ($code === T_OPEN_SQUARE_BRACKET) {
                    if ($bracketDepth > 0) {
                        $bracketDepth--;
                    } else {
                        break;
                    }
                } elseif ($code === T_CLOSE_CURLY_BRACKET || $code === T_CLOSE_SHORT_ARRAY) {
                    $braceDepth++;
                } elseif ($code === T_OPEN_CURLY_BRACKET || $code === T_OPEN_SHORT_ARRAY) {
                    if ($braceDepth > 0) {
                        $braceDepth--;
                    } else {
                        break;
                    }
                }

                // Found an equals sign at base level - this is the assignment.
                if ($code === T_EQUAL && $parenDepth === 0 && $bracketDepth === 0 && $braceDepth === 0) {
                    // Skip backwards past the equals and any whitespace.
                    $prev = $searchPtr - 1;
                    while ($prev > 0 && isset(Tokens::EMPTY_TOKENS[$tokens[$prev]['code']])) {
                        $prev--;
                    }
                    break;
                }

                // Found a semicolon or the property variable - stop searching.
                if ($code === T_SEMICOLON || $code === T_VARIABLE) {
                    break;
                }

                $searchPtr--;
            }
        }

        // Now $prev should point to the property variable (if this is a property hook).
        if ($prev <= 0 || $tokens[$prev]['code'] !== T_VARIABLE) {
            return false;
        }

        // Verify this variable is actually a property declaration by checking
        // if it's preceded by visibility modifiers, type hints, etc.
        $varPtr = $prev;

        // Search backwards for visibility/type modifiers.
        $prev--;
        while ($prev > 0 && isset(Tokens::EMPTY_TOKENS[$tokens[$prev]['code']])) {
            $prev--;
        }

        // Check for valid property declaration tokens.
        $validTokens = [
            T_PUBLIC               => true,
            T_PROTECTED            => true,
            T_PRIVATE              => true,
            T_STATIC               => true,
            T_READONLY             => true,
            T_VAR                  => true,
            T_STRING               => true, // Type hint.
            T_ARRAY                => true,
            T_CALLABLE             => true,
            T_SELF                 => true,
            T_PARENT               => true,
            T_NULLABLE             => true,
            T_TYPE_UNION           => true,
            T_TYPE_INTERSECTION    => true,
            T_NS_SEPARATOR         => true,
            T_NAME_FULLY_QUALIFIED => true,
            T_NAME_QUALIFIED       => true,
            T_NAME_RELATIVE        => true,
            T_FALSE                => true,
            T_TRUE                 => true,
            T_NULL                 => true,
            T_PUBLIC_SET           => true,
            T_PROTECTED_SET        => true,
            T_PRIVATE_SET          => true,
        ];

        // If the previous non-whitespace token is one of these, it's likely a property.
        return $prev > 0 && isset($validTokens[$tokens[$prev]['code']]);
    }

    /**
     * Check if a token is the property variable of a property with hooks.
     *
     * @param File $phpcsFile The file being scanned.
     * @param int $stackPtr The position of the T_VARIABLE token.
     * @return bool True if this variable has property hooks.
     */
    public static function isPropertyWithHooks(File $phpcsFile, int $stackPtr): bool
    {
        $tokens = $phpcsFile->getTokens();

        if ($tokens[$stackPtr]['code'] !== T_VARIABLE) {
            return false;
        }

        // Look forward for an opening brace (property hook) or semicolon (no hooks).
        $next = $stackPtr + 1;
        while ($next < $phpcsFile->numTokens) {
            $code = $tokens[$next]['code'];

            // Skip whitespace, comments, and default value assignment.
            if (isset(Tokens::EMPTY_TOKENS[$code])) {
                $next++;
                continue;
            }

            // Skip default value assignment.
            if ($code === T_EQUAL) {
                // Skip to end of default value.
                $next++;
                $depth = 0;
                while ($next < $phpcsFile->numTokens) {
                    $valCode = $tokens[$next]['code'];
                    if (
                        $valCode === T_OPEN_PARENTHESIS
                        || $valCode === T_OPEN_SQUARE_BRACKET
                        || $valCode === T_OPEN_SHORT_ARRAY
                    ) {
                        $depth++;
                    } elseif (
                        $valCode === T_CLOSE_PARENTHESIS
                        || $valCode === T_CLOSE_SQUARE_BRACKET
                        || $valCode === T_CLOSE_SHORT_ARRAY
                    ) {
                        $depth--;
                    } elseif ($depth === 0 && ($valCode === T_SEMICOLON || $valCode === T_OPEN_CURLY_BRACKET)) {
                        break;
                    }
                    $next++;
                }
                continue;
            }

            // Found a semicolon - no hooks.
            if ($code === T_SEMICOLON) {
                return false;
            }

            // Found an opening brace - has hooks.
            if ($code === T_OPEN_CURLY_BRACKET) {
                return true;
            }

            // Found something else unexpected.
            break;
        }

        return false;
    }
}
