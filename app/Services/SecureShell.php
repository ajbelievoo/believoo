<?php

namespace App\Services;

class SecureShell
{
    /**
     * Run a shell command that needs an SSH password without exposing the
     * password in the process list. A temporary file (0600) is created and
     * deleted immediately after the command finishes.
     *
     * @param string $password The raw SSH password.
     * @param string $template printf-style template; the FIRST %s placeholder is for the password file path.
     * @param mixed  ...$args  Remaining printf arguments.
     * @return string|null The command output, or null on error.
     */
    public static function exec(string $password, string $template, ...$args): ?string
    {
        $passFile = tempnam(sys_get_temp_dir(), 'sshpass_');
        file_put_contents($passFile, $password);
        chmod($passFile, 0600);

        try {
            $command = vsprintf($template, array_merge([escapeshellarg($passFile)], $args));
            return shell_exec($command);
        } finally {
            if (file_exists($passFile) && is_file($passFile)) {
                unlink($passFile);
            }
        }
    }

    /**
     * Execute a command and return the exit code and output.
     */
    public static function execWithCode(string $password, string $template, array $args, ?array &$output = null): int
    {
        $passFile = tempnam(sys_get_temp_dir(), 'sshpass_');
        file_put_contents($passFile, $password);
        chmod($passFile, 0600);

        try {
            $command = vsprintf($template, array_merge([escapeshellarg($passFile)], $args));
            return exec($command, $output);
        } finally {
            if (file_exists($passFile) && is_file($passFile)) {
                unlink($passFile);
            }
        }
    }
}
