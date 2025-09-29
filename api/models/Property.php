<?php
/**
 * Property Model
 * Handles all property-related database operations
 */

require_once __DIR__ . '/../config/config.php';

class Property {
    private $collection;
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
        $this->collection = $this->db->getCollection('properties');
    }

    /**
     * Create a new property
     */
    public function create($data) {
        try {
            $propertyData = [
                'title' => $data['title'],
                'description' => $data['description'] ?? '',
                'price' => (float)$data['price'],
                'type' => $data['type'], // 'sale' or 'rent'
                'property_type' => $data['property_type'], // 'house', 'apartment', etc.
                'bedrooms' => (int)($data['bedrooms'] ?? 0),
                'bathrooms' => (int)($data['bathrooms'] ?? 0),
                'area' => (float)($data['area'] ?? 0),
                'address' => $data['address'] ?? [],
                'features' => $data['features'] ?? [],
                'images' => [],
                'status' => $data['status'] ?? 'active', // 'active', 'pending', 'sold', 'rented'
                'agent_id' => $data['agent_id'],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime(),
                'view_count' => 0,
                'saved_by' => []
            ];

            $result = $this->collection->insertOne($propertyData);
            
            return (string)$result->getInsertedId();
            
        } catch (Exception $e) {
            error_log('Error creating property: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get property by ID
     */
    public function getById($id, $incrementView = false) {
        try {
            $property = $this->collection->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                [
                    'projection' => [
                        'saved_by' => 0 // Don't return the saved_by array by default
                    ],
                    'typeMap' => ['root' => 'array', 'document' => 'array']
                ]
            );

            if ($property && $incrementView) {
                $this->collection->updateOne(
                    ['_id' => new MongoDB\BSON\ObjectId($id)],
                    ['$inc' => ['view_count' => 1]]
                );
            }
            
            return $property;
            
        } catch (Exception $e) {
            error_log('Error getting property: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update property
     */
    public function update($id, $data) {
        try {
            $updateData = ['$set' => []];
            
            // Only include fields that are present in the data
            $updatableFields = [
                'title', 'description', 'price', 'type', 'property_type',
                'bedrooms', 'bathrooms', 'area', 'address', 'features', 'status'
            ];
            
            foreach ($updatableFields as $field) {
                if (isset($data[$field])) {
                    $updateData['$set'][$field] = $data[$field];
                }
            }
            
            // Always update the updated_at field
            $updateData['$set']['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                $updateData
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating property: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Delete property
     */
    public function delete($id) {
        try {
            $result = $this->collection->deleteOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)]
            );
            
            return $result->getDeletedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error deleting property: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all properties with pagination and filters
     */
    public function getAll($filters = [], $page = 1, $limit = 10, $sort = []) {
        try {
            $filter = [];
            
            // Apply filters
            if (isset($filters['type'])) {
                $filter['type'] = $filters['type'];
            }
            
            if (isset($filters['status'])) {
                $filter['status'] = $filters['status'];
            }
            
            if (isset($filters['min_price'])) {
                $filter['price']['$gte'] = (float)$filters['min_price'];
            }
            
            if (isset($filters['max_price'])) {
                $filter['price']['$lte'] = (float)$filters['max_price'];
            }
            
            if (isset($filters['bedrooms'])) {
                $filter['bedrooms'] = (int)$filters['bedrooms'];
            }
            
            if (isset($filters['bathrooms'])) {
                $filter['bathrooms'] = (int)$filters['bathrooms'];
            }
            
            // Default sort
            if (empty($sort)) {
                $sort = ['created_at' => -1]; // Newest first
            }
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => $sort,
                'projection' => [
                    'description' => 0, // Don't return full description in list view
                    'saved_by' => 0
                ],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $properties = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($properties as &$property) {
                $property['id'] = (string)$property['_id'];
                unset($property['_id']);
            }
            
            // Get total count for pagination
            $total = $this->collection->countDocuments($filter);
            
            return [
                'data' => $properties,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting properties: ' . $e->getMessage());
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
     * Save property to user's saved list
     */
    public function saveProperty($propertyId, $userId) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($propertyId)],
                [
                    '$addToSet' => ['saved_by' => $userId],
                    '$set' => ['updated_at' => new MongoDB\BSON\UTCDateTime()]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error saving property: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove property from user's saved list
     */
    public function unsaveProperty($propertyId, $userId) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($propertyId)],
                [
                    '$pull' => ['saved_by' => $userId],
                    '$set' => ['updated_at' => new MongoDB\BSON\UTCDateTime()]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error unsaving property: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get saved properties for a user
     */
    public function getSavedProperties($userId, $page = 1, $limit = 10) {
        try {
            $filter = ['saved_by' => $userId];
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => ['updated_at' => -1],
                'projection' => [
                    'saved_by' => 0 // Don't return the saved_by array
                ],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $properties = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($properties as &$property) {
                $property['id'] = (string)$property['_id'];
                unset($property['_id']);
            }
            
            // Get total count for pagination
            $total = $this->collection->countDocuments($filter);
            
            return [
                'data' => $properties,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting saved properties: ' . $e->getMessage());
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
     * Get recently viewed properties for a user
     */
    public function getRecentlyViewed($userId, $limit = 5) {
        try {
            // In a real app, you would have a separate collection for tracking views
            // For simplicity, we'll just return the most recent properties
            $options = [
                'limit' => $limit,
                'sort' => ['updated_at' => -1],
                'projection' => [
                    'description' => 0,
                    'saved_by' => 0
                ],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find([], $options);
            $properties = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($properties as &$property) {
                $property['id'] = (string)$property['_id'];
                unset($property['_id']);
            }
            
            return $properties;
            
        } catch (Exception $e) {
            error_log('Error getting recently viewed properties: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Search properties by text
     */
    public function search($query, $filters = [], $page = 1, $limit = 10) {
        try {
            $filter = [];
            
            // Text search
            if (!empty($query)) {
                $filter['$text'] = ['$search' => $query];
            }
            
            // Apply additional filters
            if (isset($filters['type'])) {
                $filter['type'] = $filters['type'];
            }
            
            if (isset($filters['min_price'])) {
                $filter['price']['$gte'] = (float)$filters['min_price'];
            }
            
            if (isset($filters['max_price'])) {
                $filter['price']['$lte'] = (float)$filters['max_price'];
            }
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'projection' => [
                    'description' => 0,
                    'saved_by' => 0,
                    'score' => ['$meta' => 'textScore']
                ],
                'sort' => [
                    'score' => ['$meta' => 'textScore']
                ],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $properties = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($properties as &$property) {
                $property['id'] = (string)$property['_id'];
                unset($property['_id']);
                unset($property['score']);
            }
            
            // Get total count for pagination
            $total = $this->collection->countDocuments($filter);
            
            return [
                'data' => $properties,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error searching properties: ' . $e->getMessage());
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
     * Get featured properties
     */
    public function getFeatured($limit = 6) {
        try {
            $options = [
                'limit' => $limit,
                'sort' => ['view_count' => -1, 'created_at' => -1],
                'projection' => [
                    'description' => 0,
                    'saved_by' => 0
                ],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find(['status' => 'active'], $options);
            $properties = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($properties as &$property) {
                $property['id'] = (string)$property['_id'];
                unset($property['_id']);
            }
            
            return $properties;
            
        } catch (Exception $e) {
            error_log('Error getting featured properties: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get similar properties
     */
    public function getSimilar($propertyId, $limit = 4) {
        try {
            // Get the current property to find similar ones
            $currentProperty = $this->getById($propertyId);
            if (!$currentProperty) {
                return [];
            }

            $filter = [
                '_id' => ['$ne' => new MongoDB\BSON\ObjectId($propertyId)],
                'status' => 'active',
                '$or' => [
                    ['property_type' => $currentProperty['property_type']],
                    ['price' => [
                        '$gte' => $currentProperty['price'] * 0.8,
                        '$lte' => $currentProperty['price'] * 1.2
                    ]]
                ]
            ];

            $options = [
                'limit' => $limit,
                'sort' => ['created_at' => -1],
                'projection' => [
                    'description' => 0,
                    'saved_by' => 0
                ],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $properties = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($properties as &$property) {
                $property['id'] = (string)$property['_id'];
                unset($property['_id']);
            }
            
            return $properties;
            
        } catch (Exception $e) {
            error_log('Error getting similar properties: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get properties by agent
     */
    public function getByAgent($agentId, $page = 1, $limit = 10) {
        try {
            $filter = ['agent_id' => $agentId];
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => ['created_at' => -1],
                'projection' => [
                    'saved_by' => 0
                ],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $properties = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($properties as &$property) {
                $property['id'] = (string)$property['_id'];
                unset($property['_id']);
            }
            
            // Get total count for pagination
            $total = $this->collection->countDocuments($filter);
            
            return [
                'data' => $properties,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting agent properties: ' . $e->getMessage());
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
     * Update property status
     */
    public function updateStatus($id, $status) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                [
                    '$set' => [
                        'status' => $status,
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating property status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get property statistics
     */
    public function getStats($propertyId) {
        try {
            $property = $this->getById($propertyId);
            if (!$property) {
                return null;
            }

            return [
                'views' => $property['view_count'] ?? 0,
                'saves' => count($property['saved_by'] ?? []),
                'created_at' => $property['created_at'],
                'updated_at' => $property['updated_at']
            ];
            
        } catch (Exception $e) {
            error_log('Error getting property stats: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Add property image
     */
    public function addImage($propertyId, $imageData) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($propertyId)],
                [
                    '$push' => ['images' => $imageData],
                    '$set' => ['updated_at' => new MongoDB\BSON\UTCDateTime()]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error adding property image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Remove property image
     */
    public function removeImage($propertyId, $imageId) {
        try {
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($propertyId)],
                [
                    '$pull' => ['images' => ['id' => $imageId]],
                    '$set' => ['updated_at' => new MongoDB\BSON\UTCDateTime()]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error removing property image: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Count total properties
     */
    public function count($status = null) {
        try {
            $filter = [];
            if ($status) {
                $filter['status'] = $status;
            }
            return $this->collection->countDocuments($filter);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Count properties by status
     */
    public function countByStatus($status) {
        try {
            return $this->collection->countDocuments(['status' => $status]);
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Get recent properties
     */
    public function getRecent($limit = 5) {
        try {
            return $this->collection->find([], [
                'sort' => ['created_at' => -1],
                'limit' => $limit,
                'projection' => ['title' => 1, 'price' => 1, 'status' => 1, 'created_at' => 1]
            ])->toArray();
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Get properties created today
     */
    public function getCreatedToday() {
        try {
            $startOfDay = new MongoDB\BSON\UTCDateTime(strtotime('today') * 1000);
            return $this->collection->countDocuments(['created_at' => ['$gte' => $startOfDay]]);
        } catch (Exception $e) {
            return 0;
        }
    }

}
