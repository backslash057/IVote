<?php

/**
 * Limitation de débit sans dépendance (fichiers + flock, basés dans le
 * répertoire temporaire système). En cas de panne du mécanisme, on autorise
 * la requête mais on journalise : la disponibilité prime sur la restriction.
 */
class RateLimiter {

    /**
     * Comptabilise une tentative et indique si elle est encore autorisée.
     *
     * @param string $bucket        Catégorie d'événement (login, vote, ...)
     * @param string $key           Identité du client (IP, email, ...)
     * @param int    $maxAttempts   Tentatives autorisées dans la fenêtre
     * @param int    $windowSeconds Durée de la fenêtre glissante
     */
    public static function hit(string $bucket, string $key, int $maxAttempts, int $windowSeconds): bool {
        $directory = rtrim((string)sys_get_temp_dir(), DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'ivote-ratelimit';

        if (!is_dir($directory) && !@mkdir($directory, 0700, true) && !is_dir($directory)) {
            error_log('[IVote] RateLimiter : répertoire de travail inaccessible, limite non appliquée.');
            return true;
        }

        if (random_int(1, 100) === 1) {
            self::purge($directory, $windowSeconds * 4);
        }

        $file = $directory . DIRECTORY_SEPARATOR . sha1($bucket . '|' . $key) . '.json';
        $handle = @fopen($file, 'c+');
        if ($handle === false) {
            error_log('[IVote] RateLimiter : ouverture du compteur impossible, limite non appliquée.');
            return true;
        }

        $allowed = true;

        try {
            if (!flock($handle, LOCK_EX)) {
                return true;
            }

            $now = time();
            $state = json_decode((string)stream_get_contents($handle), true);

            if (!is_array($state) || (int)($state['reset'] ?? 0) <= $now) {
                $state = ['reset' => $now + $windowSeconds, 'count' => 0];
            }

            $state['count'] = (int)$state['count'] + 1;
            $allowed = $state['count'] <= $maxAttempts;

            rewind($handle);
            ftruncate($handle, 0);
            fwrite($handle, json_encode($state));
            fflush($handle);
            flock($handle, LOCK_UN);

            if ((int)$state['count'] === $maxAttempts + 1) {
                error_log('[IVote] RateLimiter : seuil atteint pour ' . $bucket . ' (clé ' . substr($key, 0, 64) . ').');
            }
        } finally {
            fclose($handle);
        }

        return $allowed;
    }

    private static function purge(string $directory, int $maxAge): void {
        $files = @glob($directory . DIRECTORY_SEPARATOR . '*.json');
        if (!is_array($files)) {
            return;
        }

        $threshold = time() - $maxAge;
        foreach ($files as $file) {
            if (@filemtime($file) !== false && filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }
}
