<?php
/**
 * Admin Model
 * Handles admin-specific operations and statistics
 */

require_once __DIR__ . '/../config/config.php';

class Admin {
    private $db;
    private $usersCollection;
    private $propertiesCollection;
    private $applicationsCollection;
    private $transactionsCollection;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->usersCollection = $this->db->getCollection('users');
        $this->propertiesCollection = $this->db->getCollection('properties');
        $this->applicationsCollection = $this->db->getCollection('agent_applications');
        $this->transactionsCollection = $this->db->getCollection('transactions');
    }

    /**
     * Get dashboard statistics
     */
    public function getDashboardStats() {
        try {
            $stats = [
                'users' => $this->getUserStats(),
                'properties' => $this->getPropertyStats(),
                'agents' => $this->getAgentStats(),
                'applications' => $this->getApplicationStats(),
                'transactions' => $this->getTransactionStats()
            ];
            
            return $stats;
            
        } catch (Exception $e) {
            error_log('Error getting dashboard stats: ' . $e->getMessage());
            return [
                'users' => ['total' => 0, 'active' => 0, 'new_this_month' => 0],
                'properties' => ['total' => 0, 'active' => 0, 'sold' => 0, 'rented' => 0],
                'agents' => ['total' => 0, 'active' => 0],
                'applications' => ['pending' => 0, 'approved' => 0, 'rejected' => 0],
                'transactions' => ['total' => 0, 'this_month' => 0, 'revenue' => 0]
            ];
        }
    }

    /**
     * Get user statistics
     */
    private function getUserStats() {
        try {
            $total = $this->usersCollection->countDocuments([]);
            $active = $this->usersCollection->countDocuments(['status' => 'active']);
            
            // Users registered this month
            $startOfMonth = new MongoDB\BSON\UTCDateTime(strtotime('first day of this month') * 1000);
            $newThisMonth = $this->usersCollection->countDocuments([
                'created_at' => ['$gte' => $startOfMonth]
            ]);
            
            return [
                'total' => $total,
                'active' => $active,
                'new_this_month' => $newThisMonth
            ];
            
        } catch (Exception $e) {
            error_log('Error getting user stats: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'new_this_month' => 0];
        }
    }

    /**
     * Get property statistics
     */
    private function getPropertyStats() {
        try {
            $total = $this->propertiesCollection->countDocuments([]);
            $active = $this->propertiesCollection->countDocuments(['status' => 'active']);
            $sold = $this->propertiesCollection->countDocuments(['status' => 'sold']);
            $rented = $this->propertiesCollection->countDocuments(['status' => 'rented']);
            
            return [
                'total' => $total,
                'active' => $active,
                'sold' => $sold,
                'rented' => $rented
            ];
            
        } catch (Exception $e) {
            error_log('Error getting property stats: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'sold' => 0, 'rented' => 0];
        }
    }

    /**
     * Get agent statistics
     */
    private function getAgentStats() {
        try {
            $total = $this->usersCollection->countDocuments(['role' => 'agent']);
            $active = $this->usersCollection->countDocuments([
                'role' => 'agent',
                'status' => 'active'
            ]);
            
            return [
                'total' => $total,
                'active' => $active
            ];
            
        } catch (Exception $e) {
            error_log('Error getting agent stats: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0];
        }
    }

    /**
     * Get application statistics
     */
    private function getApplicationStats() {
        try {
            $pending = $this->applicationsCollection->countDocuments(['status' => 'pending']);
            $approved = $this->applicationsCollection->countDocuments(['status' => 'approved']);
            $rejected = $this->applicationsCollection->countDocuments(['status' => 'rejected']);
            
            return [
                'pending' => $pending,
                'approved' => $approved,
                'rejected' => $rejected
            ];
            
        } catch (Exception $e) {
            error_log('Error getting application stats: ' . $e->getMessage());
            return ['pending' => 0, 'approved' => 0, 'rejected' => 0];
        }
    }

    /**
     * Get transaction statistics
     */
    private function getTransactionStats() {
        try {
            $total = $this->transactionsCollection->countDocuments([]);
            
            // Transactions this month
            $startOfMonth = new MongoDB\BSON\UTCDateTime(strtotime('first day of this month') * 1000);
            $thisMonth = $this->transactionsCollection->countDocuments([
                'created_at' => ['$gte' => $startOfMonth]
            ]);
            
            // Calculate total revenue
            $pipeline = [
                [
                    '$group' => [
                        '_id' => null,
                        'total_revenue' => ['$sum' => '$amount']
                    ]
                ]
            ];
            
            $cursor = $this->transactionsCollection->aggregate($pipeline);
            $result = iterator_to_array($cursor);
            $revenue = isset($result[0]) ? $result[0]['total_revenue'] : 0;
            
            return [
                'total' => $total,
                'this_month' => $thisMonth,
                'revenue' => $revenue
            ];
            
        } catch (Exception $e) {
            error_log('Error getting transaction stats: ' . $e->getMessage());
            return ['total' => 0, 'this_month' => 0, 'revenue' => 0];
        }
    }

    /**
     * Get registration statistics for charts
     */
    public function getRegistrationStats($period = '6months') {
        try {
            $startDate = match($period) {
                '7days' => strtotime('-7 days'),
                '30days' => strtotime('-30 days'),
                '6months' => strtotime('-6 months'),
                '1year' => strtotime('-1 year'),
                default => strtotime('-6 months')
            };
            
            $startDateTime = new MongoDB\BSON\UTCDateTime($startDate * 1000);
            
            $pipeline = [
                [
                    '$match' => [
                        'created_at' => ['$gte' => $startDateTime]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'year' => ['$year' => '$created_at'],
                            'month' => ['$month' => '$created_at'],
                            'day' => ['$dayOfMonth' => '$created_at']
                        ],
                        'count' => ['$sum' => 1]
                    ]
                ],
                [
                    '$sort' => ['_id' => 1]
                ]
            ];
            
            $cursor = $this->usersCollection->aggregate($pipeline);
            $results = iterator_to_array($cursor);
            
            return $results;
            
        } catch (Exception $e) {
            error_log('Error getting registration stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get property statistics for charts
     */
    public function getPropertyChartStats($period = '6months') {
        try {
            $startDate = match($period) {
                '7days' => strtotime('-7 days'),
                '30days' => strtotime('-30 days'),
                '6months' => strtotime('-6 months'),
                '1year' => strtotime('-1 year'),
                default => strtotime('-6 months')
            };
            
            $startDateTime = new MongoDB\BSON\UTCDateTime($startDate * 1000);
            
            $pipeline = [
                [
                    '$match' => [
                        'created_at' => ['$gte' => $startDateTime]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'status' => '$status',
                            'year' => ['$year' => '$created_at'],
                            'month' => ['$month' => '$created_at']
                        ],
                        'count' => ['$sum' => 1]
                    ]
                ],
                [
                    '$sort' => ['_id' => 1]
                ]
            ];
            
            $cursor = $this->propertiesCollection->aggregate($pipeline);
            $results = iterator_to_array($cursor);
            
            return $results;
            
        } catch (Exception $e) {
            error_log('Error getting property chart stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get financial statistics
     */
    public function getFinancialStats($period = '6months') {
        try {
            $startDate = match($period) {
                '7days' => strtotime('-7 days'),
                '30days' => strtotime('-30 days'),
                '6months' => strtotime('-6 months'),
                '1year' => strtotime('-1 year'),
                default => strtotime('-6 months')
            };
            
            $startDateTime = new MongoDB\BSON\UTCDateTime($startDate * 1000);
            
            $pipeline = [
                [
                    '$match' => [
                        'created_at' => ['$gte' => $startDateTime]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'year' => ['$year' => '$created_at'],
                            'month' => ['$month' => '$created_at']
                        ],
                        'total_amount' => ['$sum' => '$amount'],
                        'count' => ['$sum' => 1]
                    ]
                ],
                [
                    '$sort' => ['_id' => 1]
                ]
            ];
            
            $cursor = $this->transactionsCollection->aggregate($pipeline);
            $results = iterator_to_array($cursor);
            
            return $results;
            
        } catch (Exception $e) {
            error_log('Error getting financial stats: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system activities (recent actions)
     */
    public function getSystemActivities($limit = 20) {
        try {
            // This would typically come from an activity log collection
            // For now, we'll simulate with recent user registrations and property additions
            $activities = [];
            
            // Recent user registrations
            $recentUsers = $this->usersCollection->find(
                [],
                [
                    'sort' => ['created_at' => -1],
                    'limit' => $limit / 2,
                    'projection' => ['fullname' => 1, 'email' => 1, 'role' => 1, 'created_at' => 1],
                    'typeMap' => ['root' => 'array', 'document' => 'array']
                ]
            );
            
            foreach ($recentUsers as $user) {
                $activities[] = [
                    'type' => 'user_registration',
                    'message' => "New {$user['role']} registered: {$user['fullname']}",
                    'timestamp' => $user['created_at'],
                    'user' => $user['fullname']
                ];
            }
            
            // Recent property additions
            $recentProperties = $this->propertiesCollection->find(
                [],
                [
                    'sort' => ['created_at' => -1],
                    'limit' => $limit / 2,
                    'projection' => ['title' => 1, 'agent_id' => 1, 'created_at' => 1],
                    'typeMap' => ['root' => 'array', 'document' => 'array']
                ]
            );
            
            foreach ($recentProperties as $property) {
                $activities[] = [
                    'type' => 'property_added',
                    'message' => "New property added: {$property['title']}",
                    'timestamp' => $property['created_at'],
                    'agent_id' => $property['agent_id']
                ];
            }
            
            // Sort activities by timestamp
            usort($activities, function($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });
            
            return array_slice($activities, 0, $limit);
            
        } catch (Exception $e) {
            error_log('Error getting system activities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get system status
     */
    public function getSystemStatus() {
        try {
            return [
                'database' => 'connected',
                'mongodb' => 'active',
                'api' => 'operational',
                'storage' => 'available',
                'last_backup' => date('Y-m-d H:i:s'), // This would come from actual backup system
                'uptime' => '99.9%' // This would come from monitoring system
            ];
            
        } catch (Exception $e) {
            error_log('Error getting system status: ' . $e->getMessage());
            return [
                'database' => 'error',
                'mongodb' => 'error',
                'api' => 'error',
                'storage' => 'error',
                'last_backup' => 'unknown',
                'uptime' => 'unknown'
            ];
        }
    }
}
