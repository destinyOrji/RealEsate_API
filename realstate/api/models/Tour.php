<?php
require_once __DIR__ . '/../config/config.php';

class Tour {
    private $db;
    private $collection;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->collection = $this->db->selectCollection('tours');
    }

    public function create($data) {
        try {
            $data['created_at'] = new MongoDB\BSON\UTCDateTime();
            $data['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $this->collection->insertOne($data);
            return $result->getInsertedId();
        } catch (Exception $e) {
            throw new Exception('Failed to create tour: ' . $e->getMessage());
        }
    }

    public function getById($id) {
        try {
            return $this->collection->findOne(['_id' => new MongoDB\BSON\ObjectId($id)]);
        } catch (Exception $e) {
            return null;
        }
    }

    public function getByUserId($userId) {
        try {
            return $this->collection->find(['user_id' => $userId])->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    public function getByPropertyId($propertyId) {
        try {
            return $this->collection->find(['property_id' => $propertyId])->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    public function update($id, $data) {
        try {
            $data['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                ['$set' => $data]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            return false;
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

    public function getAll($page = 1, $limit = 10) {
        try {
            $skip = ($page - 1) * $limit;
            return $this->collection->find([], [
                'skip' => $skip,
                'limit' => $limit,
                'sort' => ['created_at' => -1]
            ])->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    public function count() {
        try {
            return $this->collection->countDocuments();
        } catch (Exception $e) {
            return 0;
        }
    }
}
?>
