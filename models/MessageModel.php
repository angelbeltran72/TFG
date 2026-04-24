<?php
class MessageModel {

    /**
     * Devuelve la conversación entre dos usuarios, creándola si no existe.
     * Siempre almacena user1_id < user2_id para evitar duplicados.
     */
    public static function getOrCreateConversation(int $userA, int $userB): array {
        [$u1, $u2] = [min($userA, $userB), max($userA, $userB)];
        $db = SPDO::singleton();

        $st = $db->prepare("SELECT * FROM conversations WHERE user1_id = ? AND user2_id = ?");
        $st->execute([$u1, $u2]);
        $conv = $st->fetch(PDO::FETCH_ASSOC);
        if ($conv) return $conv;

        $db->prepare("INSERT INTO conversations (user1_id, user2_id) VALUES (?, ?)")
           ->execute([$u1, $u2]);
        $id = (int)$db->lastInsertId();

        $st = $db->prepare("SELECT * FROM conversations WHERE id = ?");
        $st->execute([$id]);
        return $st->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Lista las conversaciones del usuario con preview del último mensaje y conteo no leídos.
     */
    public static function getConversationsForUser(int $userId, int $limit = 30): array {
        $db = SPDO::singleton();
        $st = $db->prepare("
            SELECT
                c.id,
                c.last_message_at,
                u.id          AS other_id,
                u.nombre      AS other_nombre,
                u.avatar_path AS other_avatar,
                (SELECT content FROM messages
                 WHERE conversation_id = c.id
                 ORDER BY created_at DESC LIMIT 1)           AS last_content,
                (SELECT COUNT(*) FROM messages
                 WHERE conversation_id = c.id
                   AND sender_id != :uid2
                   AND read_at IS NULL)                      AS unread
            FROM conversations c
            JOIN users u ON u.id = IF(c.user1_id = :uid3, c.user2_id, c.user1_id)
            WHERE c.user1_id = :uid4 OR c.user2_id = :uid5
            ORDER BY COALESCE(c.last_message_at, c.created_at) DESC
            LIMIT " . (int)$limit
        );
        $st->execute([':uid2' => $userId, ':uid3' => $userId, ':uid4' => $userId, ':uid5' => $userId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los mensajes de una conversación y marca como leídos los del interlocutor.
     */
    public static function getMessages(int $convId, int $userId, int $limit = 50): array {
        $db = SPDO::singleton();
        $db->prepare("UPDATE messages SET read_at = NOW()
                      WHERE conversation_id = ? AND sender_id != ? AND read_at IS NULL")
           ->execute([$convId, $userId]);

        $st = $db->prepare("
            SELECT m.id, m.sender_id, m.content, m.read_at, m.created_at,
                   u.nombre AS sender_nombre, u.avatar_path AS sender_avatar
            FROM messages m
            JOIN users u ON u.id = m.sender_id
            WHERE m.conversation_id = ?
            ORDER BY m.created_at ASC
            LIMIT " . (int)$limit
        );
        $st->execute([$convId]);
        return $st->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Envía un mensaje y actualiza last_message_at en la conversación.
     */
    public static function sendMessage(int $convId, int $senderId, string $content): int {
        $db = SPDO::singleton();
        $db->prepare("INSERT INTO messages (conversation_id, sender_id, content) VALUES (?, ?, ?)")
           ->execute([$convId, $senderId, $content]);
        $id = (int)$db->lastInsertId();
        $db->prepare("UPDATE conversations SET last_message_at = NOW() WHERE id = ?")
           ->execute([$convId]);
        return $id;
    }

    /**
     * Total de mensajes no leídos para el usuario en todas sus conversaciones.
     */
    public static function getUnreadCount(int $userId): int {
        $db = SPDO::singleton();
        $st = $db->prepare("
            SELECT COUNT(*) FROM messages m
            JOIN conversations c ON c.id = m.conversation_id
            WHERE (c.user1_id = ? OR c.user2_id = ?)
              AND m.sender_id != ?
              AND m.read_at IS NULL
        ");
        $st->execute([$userId, $userId, $userId]);
        return (int)$st->fetchColumn();
    }

    /**
     * Verifica que un usuario sea participante de la conversación (seguridad).
     */
    public static function isParticipant(int $convId, int $userId): bool {
        $db = SPDO::singleton();
        $st = $db->prepare("SELECT id FROM conversations WHERE id = ? AND (user1_id = ? OR user2_id = ?)");
        $st->execute([$convId, $userId, $userId]);
        return (bool)$st->fetch();
    }
}
