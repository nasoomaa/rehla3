<?php

declare(strict_types=1);

namespace Tests\Architecture\Support;

final class ArchitectureScanner
{
    /**
     * @return array<string, list<string>> Map of package name to list of allowed package dependencies
     */
    public static function loadPackageMap(): array
    {
        $path = base_path('docs/architecture/rehla-package-map.json');
        $data = json_decode((string) file_get_contents($path), true, flags: JSON_THROW_ON_ERROR);

        return $data['packages'];
    }

    /**
     * Parse all PHP files in a directory and return all referenced Rehla classes and uses.
     *
     * @return list<array{file: string, line: int, referenced: string, package: string}>
     */
    public static function scanFileReferences(string $filePath): array
    {
        $code = (string) file_get_contents($filePath);

        return self::scanCodeReferences($code, $filePath);
    }

    /**
     * @return list<array{file: string, line: int, referenced: string, package: string}>
     */
    public static function scanCodeReferences(string $code, string $filePath = 'inline'): array
    {
        $tokens = token_get_all($code);
        $references = [];
        $count = count($tokens);

        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];

            // Handle `use` statements
            if (is_array($token) && $token[0] === T_USE) {
                $line = $token[2];
                $i++;
                $useTokens = [];
                while ($i < $count && $tokens[$i] !== ';' && $tokens[$i] !== '{' && $tokens[$i] !== ')') {
                    $useTokens[] = $tokens[$i];
                    $i++;
                }

                // Check for grouped use: e.g. use Rehla\Wallet\Models\{Wallet, LedgerEntry};
                if ($i < $count && $tokens[$i] === '{') {
                    $prefix = self::stringifyTokens($useTokens);
                    $i++; // past '{'
                    $groupItem = [];
                    while ($i < $count && $tokens[$i] !== '}') {
                        if ($tokens[$i] === ',') {
                            $item = trim(self::stringifyTokens($groupItem));
                            if ($item !== '') {
                                $full = trim($prefix, "\\ \t\n\r\0\x0B").'\\'.ltrim($item, '\\');
                                self::collectIfRehla($full, $filePath, $line, $references);
                            }
                            $groupItem = [];
                        } else {
                            $groupItem[] = $tokens[$i];
                        }
                        $i++;
                    }
                    $item = trim(self::stringifyTokens($groupItem));
                    if ($item !== '') {
                        $full = trim($prefix, "\\ \t\n\r\0\x0B").'\\'.ltrim($item, '\\');
                        self::collectIfRehla($full, $filePath, $line, $references);
                    }
                    // skip to semicolon
                    while ($i < $count && $tokens[$i] !== ';') {
                        $i++;
                    }
                } else {
                    // Regular or comma-separated use statement
                    $useStr = self::stringifyTokens($useTokens);
                    $parts = explode(',', $useStr);
                    foreach ($parts as $part) {
                        $part = trim($part);
                        // handle alias: `use Foo\Bar as Baz;`
                        if (stripos($part, ' as ') !== false) {
                            $part = trim(explode(' as ', $part)[0]);
                        }
                        self::collectIfRehla($part, $filePath, $line, $references);
                    }
                }

                continue;
            }

            // Handle T_NAME_QUALIFIED and T_NAME_FULLY_QUALIFIED in code body
            if (is_array($token) && in_array($token[0], [T_NAME_QUALIFIED, T_NAME_FULLY_QUALIFIED], true)) {
                $className = $token[1];
                $line = $token[2];
                self::collectIfRehla($className, $filePath, $line, $references);
            }
        }

        return $references;
    }

    private static function stringifyTokens(array $tokens): string
    {
        $str = '';
        foreach ($tokens as $token) {
            $str .= is_array($token) ? $token[1] : $token;
        }

        return $str;
    }

    private static function collectIfRehla(string $name, string $file, int $line, array &$references): void
    {
        $normalized = ltrim($name, '\\');
        if (str_starts_with($normalized, 'Rehla\\')) {
            $parts = explode('\\', $normalized);
            $targetPackage = $parts[1] ?? '';
            $references[] = [
                'file' => $file,
                'line' => $line,
                'referenced' => $normalized,
                'package' => $targetPackage,
            ];
        }
    }
}
