<?php
/**
 * Agent Application Model
 * Handles agent application operations
 */

require_once __DIR__ . '/../config/config.php';

use MongoDB\BSON\ObjectId;

class AgentApplication {
    private $collection;
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->collection = $this->db->getCollection('agent_applications');
    }
    
    /**
     * Create a new agent application
     * 
     * @param array $data Application data
     * @return array|bool Created application or false on failure
     */
    public function create($data) {
        try {
            // Prepare application data
            $applicationData = [
                'fullname' => $data['fullname'],
                'email' => strtolower(trim($data['email'])),
                'phone' => $data['phone'] ?? '',
                'experience' => $data['experience'] ?? '',
                'license_number' => $data['license_number'] ?? '',
                'company' => $data['company'] ?? '',
                'bio' => $data['bio'] ?? '',
                'specializations' => $data['specializations'] ?? [],
                'status' => 'pending',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];
            
            // Insert into database
            $result = $this->collection->insertOne($applicationData);
            
            if ($result->getInsertedCount() > 0) {
                $applicationData['id'] = (string) $result->getInsertedId();
                unset($applicationData['_id']);
                return $applicationData;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Error creating agent application: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get all agent applications
     * 
     * @return array Applications
     */
    public function getAll($page = 1, $limit = 10, $status = null) {
        try {
            $filter = [];
            if ($status) {
                $filter['status'] = $status;
            }
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => ['created_at' => -1],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $applications = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($applications as &$application) {
                $application['id'] = (string)$application['_id'];
                unset($application['_id']);
            }
            
            // Get total count for pagination
            $total = $this->collection->countDocuments($filter);
            
            return [
                'data' => $applications,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting agent applications: ' . $e->getMessage());
            return [
                'data' => [],
                'pagination' => [
                    'total' => 0,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => 0
                ]
            ];
        }
    }
    
    /**
     * Get agent application by ID
     * 
     * @param string $id Application ID
     * @return array|null Application or null if not found
     */
    public function getById($id) {
        try {
            $objectId = new ObjectId($id);
            $document = $this->collection->findOne(
                ['_id' => $objectId],
                ['typeMap' => ['root' => 'array', 'document' => 'array']]
            );
            
            if ($document) {
                $document['id'] = (string) $document['_id'];
                unset($document['_id']);
                return $document;
            }
            
            return null;
        } catch (Exception $e) {
            error_log('Error getting agent application by ID: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Update agent application status
     * 
     * @param string $id Application ID
     * @param string $status New status
     * @return bool Success or failure
     */
    public function updateStatus($id, $status) {
        try {
            $objectId = new ObjectId($id);
            $result = $this->collection->updateOne(
                ['_id' => $objectId],
                ['$set' => [
                    'status' => $status,
                    'updated_at' => new MongoDB\BSON\UTCDateTime()
                ]]
            );
            
            return $result->getModifiedCount() > 0;
        } catch (Exception $e) {
            error_log('Error updating agent application status: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Delete agent application
     * 
     * @param string $id Application ID
     * @return bool Success or failure
     */
    public function delete($id) {
        try {
            $objectId = new ObjectId($id);
            $result = $this->collection->deleteOne(['_id' => $objectId]);
            
            return $result->getDeletedCount() > 0;
        } catch (Exception $e) {
            error_log('Error deleting agent application: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Check if email already has an application
     */
    public function emailExists($email) {
        try {
            $count = $this->collection->countDocuments([
                'email' => strtolower(trim($email))
            ]);
            
            return $count > 0;
        } catch (Exception $e) {
            error_log('Error checking email existence: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get applications by status
     */
    public function getByStatus($status, $page = 1, $limit = 10) {
        return $this->getAll($page, $limit, $status);
    }

    /**
     * Get application statistics
     */
    public function getStats() {
        try {
            $pipeline = [
                [
                    '$group' => [
                        '_id' => '$status',
                        'count' => ['$sum' => 1]
                    ]
                ]
            ];
            
            $cursor = $this->collection->aggregate($pipeline);
            $stats = iterator_to_array($cursor);
            
            $result = [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];
            
            foreach ($stats as $stat) {
                $result[$stat['_id']] = $stat['count'];
                $result['total'] += $stat['count'];
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log('Error getting application stats: ' . $e->getMessage());
            return [
                'pending' => 0,
                'approved' => 0,
                'rejected' => 0,
                'total' => 0
            ];
        }
    }
}
