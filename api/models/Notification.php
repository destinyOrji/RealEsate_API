<?php
require_once __DIR__ . '/../config/config.php';

class Notification {
    private $db;
    private $collection;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->collection = $this->db->selectCollection('notifications');
    }

    public function create($data) {
        try {
            $data['created_at'] = new MongoDB\BSON\UTCDateTime();
            $data['read'] = false;
            
            $result = $this->collection->insertOne($data);
            return $result->getInsertedId();
        } catch (Exception $e) {
            throw new Exception('Failed to create notification: ' . $e->getMessage());
        }
    }

    public function getById($id) {
        try {
            return $this->collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getByUserId($userId, $limit = 10) {
        try {
            return $this->collection->find(
                ['user_id' => $userId],
                [
                    'sort' => ['created_at' => -1],
                    'limit' => $limit
                ]
            )->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    public function markAsRead($id) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                ['$set' => ['read' => true, 'read_at' => new MongoDB\BSON\UTCDateTime()]]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function markAllAsRead($userId) {
        try {
            $result = $this->collection->updateMany(
                ['user_id' => $userId, 'read' => false],
                ['$set' => ['read' => true, 'read_at' => new MongoDB\BSON\UTCDateTime()]]
            );
            
            return $result->getModifiedCount();
        } catch (Exception $e) {
            return 0;
        }
    }

    public function delete($id) {
        try {
            $result = $this->collection->deleteOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            return false;
        }
    }

    public function getUnreadCount($userId) {
        try {
            return $this->collection->countDocuments(['user_id' => $userId, 'read' => false]);
        } catch (Exception $e) {
            return 0;
        }
    }

    public function deleteOld($days = 30) {
        try {
            $cutoffDate = new MongoDB\BSON\UTCDateTime((time() - ($days * 24 * 60 * 60)) * 1000);
            $result = $this->collection->deleteMany(['created_at' => ['$lt' => $cutoffDate]]);
            return $result->getDeletedCount();
        } catch (Exception $e) {
            return 0;
        }
    }
}
?>
