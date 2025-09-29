<?php
/**
 * Transaction Model
 * Handles financial transactions and payments
 */

require_once __DIR__ . '/../config/config.php';

class Transaction {
    private $collection;
    private $db;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->collection = $this->db->getCollection('transactions');
    }

    /**
     * Create a new transaction
     */
    public function create($data) {
        try {
            $transactionData = [
                'user_id' => $data['user_id'],
                'property_id' => $data['property_id'] ?? null,
                'agent_id' => $data['agent_id'] ?? null,
                'type' => $data['type'], // 'sale', 'rent', 'commission', 'fee'
                'amount' => (float)$data['amount'],
                'currency' => $data['currency'] ?? 'USD',
                'status' => $data['status'] ?? 'pending', // 'pending', 'completed', 'failed', 'cancelled'
                'payment_method' => $data['payment_method'] ?? null,
                'payment_reference' => $data['payment_reference'] ?? null,
                'description' => $data['description'] ?? '',
                'metadata' => $data['metadata'] ?? [],
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];

            $result = $this->collection->insertOne($transactionData);
            
            if ($result->getInsertedCount() > 0) {
                $transactionData['id'] = (string)$result->getInsertedId();
                unset($transactionData['_id']);
                return $transactionData;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Error creating transaction: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get transaction by ID
     */
    public function getById($id) {
        try {
            $transaction = $this->collection->findOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                ['typeMap' => ['root' => 'array', 'document' => 'array']]
            );

            if ($transaction) {
                $transaction['id'] = (string)$transaction['_id'];
                unset($transaction['_id']);
                return $transaction;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('Error getting transaction: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Update transaction
     */
    public function update($id, $data) {
        try {
            $updateData = ['$set' => []];
            
            $updatableFields = [
                'status', 'payment_method', 'payment_reference', 
                'description', 'metadata'
            ];
            
            foreach ($updatableFields as $field) {
                if (isset($data[$field])) {
                    $updateData['$set'][$field] = $data[$field];
                }
            }
            
            $updateData['$set']['updated_at'] = new MongoDB\BSON\UTCDateTime();
            
            $result = $this->collection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)],
                $updateData
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating transaction: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update transaction status
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
            error_log('Error updating transaction status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all transactions with filters and pagination
     */
    public function getAll($filters = [], $page = 1, $limit = 10, $sort = []) {
        try {
            $filter = [];
            
            // Apply filters
            if (isset($filters['user_id'])) {
                $filter['user_id'] = $filters['user_id'];
            }
            
            if (isset($filters['agent_id'])) {
                $filter['agent_id'] = $filters['agent_id'];
            }
            
            if (isset($filters['property_id'])) {
                $filter['property_id'] = $filters['property_id'];
            }
            
            if (isset($filters['type'])) {
                $filter['type'] = $filters['type'];
            }
            
            if (isset($filters['status'])) {
                $filter['status'] = $filters['status'];
            }
            
            if (isset($filters['date_from'])) {
                $filter['created_at']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($filters['date_from']) * 1000);
            }
            
            if (isset($filters['date_to'])) {
                $filter['created_at']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($filters['date_to']) * 1000);
            }
            
            // Default sort
            if (empty($sort)) {
                $sort = ['created_at' => -1];
            }
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => $sort,
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->collection->find($filter, $options);
            $transactions = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($transactions as &$transaction) {
                $transaction['id'] = (string)$transaction['_id'];
                unset($transaction['_id']);
            }
            
            // Get total count for pagination
            $total = $this->collection->countDocuments($filter);
            
            return [
                'data' => $transactions,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting transactions: ' . $e->getMessage());
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
     * Get transactions by user
     */
    public function getByUser($userId, $page = 1, $limit = 10) {
        return $this->getAll(['user_id' => $userId], $page, $limit);
    }

    /**
     * Get transactions by agent
     */
    public function getByAgent($agentId, $page = 1, $limit = 10) {
        return $this->getAll(['agent_id' => $agentId], $page, $limit);
    }

    /**
     * Get transactions by property
     */
    public function getByProperty($propertyId, $page = 1, $limit = 10) {
        return $this->getAll(['property_id' => $propertyId], $page, $limit);
    }

    /**
     * Calculate total revenue
     */
    public function getTotalRevenue($filters = []) {
        try {
            $matchFilter = ['status' => 'completed'];
            
            // Apply additional filters
            if (isset($filters['date_from'])) {
                $matchFilter['created_at']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($filters['date_from']) * 1000);
            }
            
            if (isset($filters['date_to'])) {
                $matchFilter['created_at']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($filters['date_to']) * 1000);
            }
            
            if (isset($filters['type'])) {
                $matchFilter['type'] = $filters['type'];
            }
            
            $pipeline = [
                ['$match' => $matchFilter],
                [
                    '$group' => [
                        '_id' => null,
                        'total_revenue' => ['$sum' => '$amount'],
                        'transaction_count' => ['$sum' => 1]
                    ]
                ]
            ];
            
            $cursor = $this->collection->aggregate($pipeline);
            $result = iterator_to_array($cursor);
            
            if (isset($result[0])) {
                return [
                    'total_revenue' => $result[0]['total_revenue'],
                    'transaction_count' => $result[0]['transaction_count']
                ];
            }
            
            return [
                'total_revenue' => 0,
                'transaction_count' => 0
            ];
            
        } catch (Exception $e) {
            error_log('Error calculating total revenue: ' . $e->getMessage());
            return [
                'total_revenue' => 0,
                'transaction_count' => 0
            ];
        }
    }

    /**
     * Get revenue by period (for charts)
     */
    public function getRevenueByPeriod($period = 'month', $limit = 12) {
        try {
            $groupBy = match($period) {
                'day' => [
                    'year' => ['$year' => '$created_at'],
                    'month' => ['$month' => '$created_at'],
                    'day' => ['$dayOfMonth' => '$created_at']
                ],
                'week' => [
                    'year' => ['$year' => '$created_at'],
                    'week' => ['$week' => '$created_at']
                ],
                'month' => [
                    'year' => ['$year' => '$created_at'],
                    'month' => ['$month' => '$created_at']
                ],
                'year' => [
                    'year' => ['$year' => '$created_at']
                ],
                default => [
                    'year' => ['$year' => '$created_at'],
                    'month' => ['$month' => '$created_at']
                ]
            };
            
            $pipeline = [
                [
                    '$match' => [
                        'status' => 'completed'
                    ]
                ],
                [
                    '$group' => [
                        '_id' => $groupBy,
                        'revenue' => ['$sum' => '$amount'],
                        'count' => ['$sum' => 1]
                    ]
                ],
                [
                    '$sort' => ['_id' => -1]
                ],
                [
                    '$limit' => $limit
                ]
            ];
            
            $cursor = $this->collection->aggregate($pipeline);
            $results = iterator_to_array($cursor);
            
            return array_reverse($results); // Return in chronological order
            
        } catch (Exception $e) {
            error_log('Error getting revenue by period: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get transaction statistics
     */
    public function getStats($filters = []) {
        try {
            $matchFilter = [];
            
            // Apply filters
            if (isset($filters['date_from'])) {
                $matchFilter['created_at']['$gte'] = new MongoDB\BSON\UTCDateTime(strtotime($filters['date_from']) * 1000);
            }
            
            if (isset($filters['date_to'])) {
                $matchFilter['created_at']['$lte'] = new MongoDB\BSON\UTCDateTime(strtotime($filters['date_to']) * 1000);
            }
            
            $pipeline = [
                ['$match' => $matchFilter],
                [
                    '$group' => [
                        '_id' => '$status',
                        'count' => ['$sum' => 1],
                        'total_amount' => ['$sum' => '$amount']
                    ]
                ]
            ];
            
            $cursor = $this->collection->aggregate($pipeline);
            $results = iterator_to_array($cursor);
            
            $stats = [
                'pending' => ['count' => 0, 'amount' => 0],
                'completed' => ['count' => 0, 'amount' => 0],
                'failed' => ['count' => 0, 'amount' => 0],
                'cancelled' => ['count' => 0, 'amount' => 0],
                'total' => ['count' => 0, 'amount' => 0]
            ];
            
            foreach ($results as $result) {
                $status = $result['_id'];
                $stats[$status] = [
                    'count' => $result['count'],
                    'amount' => $result['total_amount']
                ];
                $stats['total']['count'] += $result['count'];
                $stats['total']['amount'] += $result['total_amount'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log('Error getting transaction stats: ' . $e->getMessage());
            return [
                'pending' => ['count' => 0, 'amount' => 0],
                'completed' => ['count' => 0, 'amount' => 0],
                'failed' => ['count' => 0, 'amount' => 0],
                'cancelled' => ['count' => 0, 'amount' => 0],
                'total' => ['count' => 0, 'amount' => 0]
            ];
        }
    }

    /**
     * Delete transaction
     */
    public function delete($id) {
        try {
            $result = $this->collection->deleteOne(
                ['_id' => new MongoDB\BSON\ObjectId($id)]
            );
            
            return $result->getDeletedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error deleting transaction: ' . $e->getMessage());
            return false;
        }
    }
}
