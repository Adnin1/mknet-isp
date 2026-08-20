<?php
declare(strict_types=1);

class Notifier
{
    public static function send(PDO $pdo, int $userId, string $title, string $message, string $type = 'info', ?string $link = null): void
    {
        $stmt = $pdo->prepare("INSERT INTO notifications (user_id, title, message, type, link) VALUES (?,?,?,?,?)");
        $stmt->execute([$userId, $title, $message, $type, $link]);
    }

    public static function unreadCount(PDO $pdo, int $userId): int
    {
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id=? AND is_read=0");
        $stmt->execute([$userId]);
        return (int)$stmt->fetchColumn();
    }

    public static function listForUser(PDO $pdo, int $userId, int $limit = 20): array
    {
        $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id=? ORDER BY id DESC LIMIT ?");
        $stmt->bindValue(1, $userId, PDO::PARAM_INT);
        $stmt->bindValue(2, $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll();
    }

    public static function markRead(PDO $pdo, int $userId, int $notificationId): void
    {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE id=? AND user_id=?")->execute([$notificationId, $userId]);
    }

    public static function markAllRead(PDO $pdo, int $userId): void
    {
        $pdo->prepare("UPDATE notifications SET is_read=1 WHERE user_id=? AND is_read=0")->execute([$userId]);
    }
}
