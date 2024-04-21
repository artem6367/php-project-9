<?php

namespace app;

use Carbon\Carbon;

/**
 * Класс для работы с базой данных analyzer
 */
class AnalyzerDb
{
    /**
     * @var self
     */
    private static $self = null;
    private \PDO $pdo;

    public static function connect(string $baseurl = null): self
    {
        if (null == self::$self) {
            self::$self = new self($baseurl);
        }

        return self::$self;
    }

    private function __construct(string $baseurl = null)
    {
        $env = $baseurl ?? getenv('DATABASE_URL');
        if ($env === false) {
            throw new \Exception('Параметр окружения DATABASE_URL не установлен');
        }

        $databaseUrl = parse_url($env);

        if (
            !is_array($databaseUrl)
            || !(
                array_key_exists('user', $databaseUrl)
                && array_key_exists('pass', $databaseUrl)
                && array_key_exists('host', $databaseUrl)
                && array_key_exists('path', $databaseUrl)
            )
        ) {
            throw new \Exception('Параметр окружения DATABASE_URL задан некоректно');
        }

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC
        ];
        $username = $databaseUrl['user'];
        $password = $databaseUrl['pass'];
        $dsn = sprintf("pgsql:host=%s;dbname=%s", $databaseUrl['host'], ltrim($databaseUrl['path'], '/'));

        $this->pdo = new \PDO($dsn, $username, $password, $options);
    }

    public function selectUrls(): array
    {
        $sql = 'SELECT * FROM urls ORDER BY created_at DESC';
        $stmt = $this->pdo->query($sql);
        if ($stmt === false) {
            throw new \Exception('Не удалось получить список url');
        }
        return (array) $stmt->fetchAll();
    }

    public function selectOneUrl(int $id): array
    {
        $sql = 'SELECT * FROM urls WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$id]);
        return $stmt->fetch();
    }

    public function insertUrl(string $name): int
    {
        $sql = 'INSERT INTO urls (name, created_at) VALUES (:name, :created_at)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':name' => $name, 'created_at' => Carbon::now()->toDateTimeString()]);
        $id = $this->pdo->lastInsertId();
        if ($id === false) {
            throw new \Exception('Не удалось сохранить url');
        }
        return (int) $id;
    }

    public function deleteUrl(int $id): bool
    {
        $sql = 'DELETE FROM urls WHERE id = ?';
        $stmt = $this->pdo->prepare($sql);
        return $stmt->execute([$id]);
    }

    public function selectChecks(int $urlId): array
    {
        $sql = 'SELECT * FROM url_checks WHERE url_id=:url_id ORDER BY created_at DESC';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':url_id' => $urlId]);
        if ($stmt === false) {
            throw new \Exception('Не удалось получить список проверок');
        }
        return (array) $stmt->fetchAll();
    }

    public function selectLastCheck(array $urls): array
    {
        $newUrls = [];
        foreach ($urls as $url) {
            $sql = 'SELECT * FROM url_checks WHERE url_id=:url_id ORDER BY created_at DESC LIMIT 1';
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute([':url_id' => $url['id']]);
            $newUrl = $url;
            $newUrl['last_check'] = $stmt->fetch();
            $newUrls[] = $newUrl;
        }

        return $newUrls;
    }

    public function insertCheck(int $urlId): int
    {
        $sql = 'INSERT INTO url_checks (url_id, created_at) VALUES (:url_id, :created_at)';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':url_id' => $urlId,
            'created_at' => Carbon::now()->toDateTimeString()
        ]);
        $id = $this->pdo->lastInsertId();
        if ($id === false) {
            throw new \Exception('Не удалось сохранить проверку');
        }
        return (int) $id;
    }
}