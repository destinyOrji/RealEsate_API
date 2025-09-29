<?php
/**
 * Agent Model
 * Handles agent-specific operations and dashboard data
 */

require_once __DIR__ . '/../config/config.php';

class Agent {
    private $db;
    private $usersCollection;
    private $propertiesCollection;
    private $transactionsCollection;
    private $toursCollection;
    
    public function __construct() {
        $this->db = Database::getInstance();
        $this->usersCollection = $this->db->getCollection('users');
        $this->propertiesCollection = $this->db->getCollection('properties');
        $this->transactionsCollection = $this->db->getCollection('transactions');
        $this->toursCollection = $this->db->getCollection('property_tours');
    }

    /**
     * Get agent dashboard data
     */
    public function getDashboard($agentId) {
        try {
            $dashboard = [
                'agent_info' => $this->getAgentInfo($agentId),
                'properties' => $this->getAgentPropertyStats($agentId),
                'tours' => $this->getAgentTourStats($agentId),
                'earnings' => $this->getAgentEarningsStats($agentId),
                'performance' => $this->getAgentPerformanceStats($agentId),
                'recent_activities' => $this->getRecentActivities($agentId)
            ];
            
            return $dashboard;
            
        } catch (Exception $e) {
            error_log('Error getting agent dashboard: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get agent information
     */
    public function getAgentInfo($agentId) {
        try {
            $agent = $this->usersCollection->findOne(
                [
                    '_id' => new MongoDB\BSON\ObjectId($agentId),
                    'role' => 'agent'
                ],
                [
                    'projection' => ['password' => 0],
                    'typeMap' => ['root' => 'array', 'document' => 'array']
                ]
            );

            if ($agent) {
                $agent['id'] = (string)$agent['_id'];
                unset($agent['_id']);
                return $agent;
            }
            
            return null;
            
        } catch (Exception $e) {
            error_log('Error getting agent info: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Get agent's properties with filters
     */
    public function getAgentProperties($agentId, $filters = [], $page = 1, $limit = 10) {
        try {
            $filter = ['agent_id' => $agentId];
            
            // Apply additional filters
            if (isset($filters['status'])) {
                $filter['status'] = $filters['status'];
            }
            
            if (isset($filters['type'])) {
                $filter['type'] = $filters['type'];
            }
            
            if (isset($filters['property_type'])) {
                $filter['property_type'] = $filters['property_type'];
            }
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => ['created_at' => -1],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->propertiesCollection->find($filter, $options);
            $properties = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($properties as &$property) {
                $property['id'] = (string)$property['_id'];
                unset($property['_id']);
            }
            
            // Get total count for pagination
            $total = $this->propertiesCollection->countDocuments($filter);
            
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
     * Get agent property statistics
     */
    private function getAgentPropertyStats($agentId) {
        try {
            $pipeline = [
                [
                    '$match' => ['agent_id' => $agentId]
                ],
                [
                    '$group' => [
                        '_id' => '$status',
                        'count' => ['$sum' => 1],
                        'total_value' => ['$sum' => '$price']
                    ]
                ]
            ];
            
            $cursor = $this->propertiesCollection->aggregate($pipeline);
            $results = iterator_to_array($cursor);
            
            $stats = [
                'total' => 0,
                'active' => 0,
                'sold' => 0,
                'rented' => 0,
                'pending' => 0,
                'total_value' => 0
            ];
            
            foreach ($results as $result) {
                $status = $result['_id'];
                $stats[$status] = $result['count'];
                $stats['total'] += $result['count'];
                $stats['total_value'] += $result['total_value'];
            }
            
            return $stats;
            
        } catch (Exception $e) {
            error_log('Error getting agent property stats: ' . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'sold' => 0,
                'rented' => 0,
                'pending' => 0,
                'total_value' => 0
            ];
        }
    }

    /**
     * Get scheduled tours for agent
     */
    public function getScheduledTours($agentId, $page = 1, $limit = 10) {
        try {
            $filter = ['agent_id' => $agentId];
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => ['scheduled_date' => 1], // Upcoming tours first
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->toursCollection->find($filter, $options);
            $tours = iterator_to_array($cursor);
            
            // Convert ObjectId to string for JSON serialization
            foreach ($tours as &$tour) {
                $tour['id'] = (string)$tour['_id'];
                unset($tour['_id']);
            }
            
            // Get total count for pagination
            $total = $this->toursCollection->countDocuments($filter);
            
            return [
                'data' => $tours,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting scheduled tours: ' . $e->getMessage());
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
     * Get agent tour statistics
     */
    private function getAgentTourStats($agentId) {
        try {
            $total = $this->toursCollection->countDocuments(['agent_id' => $agentId]);
            $pending = $this->toursCollection->countDocuments([
                'agent_id' => $agentId,
                'status' => 'pending'
            ]);
            $completed = $this->toursCollection->countDocuments([
                'agent_id' => $agentId,
                'status' => 'completed'
            ]);
            $cancelled = $this->toursCollection->countDocuments([
                'agent_id' => $agentId,
                'status' => 'cancelled'
            ]);
            
            return [
                'total' => $total,
                'pending' => $pending,
                'completed' => $completed,
                'cancelled' => $cancelled
            ];
            
        } catch (Exception $e) {
            error_log('Error getting agent tour stats: ' . $e->getMessage());
            return [
                'total' => 0,
                'pending' => 0,
                'completed' => 0,
                'cancelled' => 0
            ];
        }
    }

    /**
     * Get agent earnings and commissions
     */
    public function getEarnings($agentId, $period = 'all') {
        try {
            $filter = ['agent_id' => $agentId, 'status' => 'completed'];
            
            // Apply period filter
            if ($period !== 'all') {
                $startDate = match($period) {
                    'today' => strtotime('today'),
                    'week' => strtotime('-1 week'),
                    'month' => strtotime('-1 month'),
                    '3months' => strtotime('-3 months'),
                    'year' => strtotime('-1 year'),
                    default => strtotime('-1 month')
                };
                
                $filter['created_at'] = ['$gte' => new MongoDB\BSON\UTCDateTime($startDate * 1000)];
            }
            
            $pipeline = [
                ['$match' => $filter],
                [
                    '$group' => [
                        '_id' => '$type',
                        'total_amount' => ['$sum' => '$amount'],
                        'count' => ['$sum' => 1]
                    ]
                ]
            ];
            
            $cursor = $this->transactionsCollection->aggregate($pipeline);
            $results = iterator_to_array($cursor);
            
            $earnings = [
                'total_earnings' => 0,
                'commission' => 0,
                'sales' => 0,
                'rentals' => 0,
                'transaction_count' => 0
            ];
            
            foreach ($results as $result) {
                $type = $result['_id'];
                $earnings[$type] = $result['total_amount'];
                $earnings['total_earnings'] += $result['total_amount'];
                $earnings['transaction_count'] += $result['count'];
            }
            
            return $earnings;
            
        } catch (Exception $e) {
            error_log('Error getting agent earnings: ' . $e->getMessage());
            return [
                'total_earnings' => 0,
                'commission' => 0,
                'sales' => 0,
                'rentals' => 0,
                'transaction_count' => 0
            ];
        }
    }

    /**
     * Get agent earnings statistics
     */
    private function getAgentEarningsStats($agentId) {
        return $this->getEarnings($agentId, 'month');
    }

    /**
     * Get agent performance metrics
     */
    public function getPerformanceMetrics($agentId, $period = '6months') {
        try {
            $startDate = match($period) {
                '30days' => strtotime('-30 days'),
                '3months' => strtotime('-3 months'),
                '6months' => strtotime('-6 months'),
                '1year' => strtotime('-1 year'),
                default => strtotime('-6 months')
            };
            
            $startDateTime = new MongoDB\BSON\UTCDateTime($startDate * 1000);
            
            // Properties performance
            $propertiesPipeline = [
                [
                    '$match' => [
                        'agent_id' => $agentId,
                        'created_at' => ['$gte' => $startDateTime]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'year' => ['$year' => '$created_at'],
                            'month' => ['$month' => '$created_at']
                        ],
                        'properties_listed' => ['$sum' => 1],
                        'properties_sold' => [
                            '$sum' => [
                                '$cond' => [
                                    ['$in' => ['$status', ['sold', 'rented']]],
                                    1,
                                    0
                                ]
                            ]
                        ]
                    ]
                ],
                ['$sort' => ['_id' => 1]]
            ];
            
            $propertiesCursor = $this->propertiesCollection->aggregate($propertiesPipeline);
            $propertiesResults = iterator_to_array($propertiesCursor);
            
            // Tours performance
            $toursPipeline = [
                [
                    '$match' => [
                        'agent_id' => $agentId,
                        'created_at' => ['$gte' => $startDateTime]
                    ]
                ],
                [
                    '$group' => [
                        '_id' => [
                            'year' => ['$year' => '$created_at'],
                            'month' => ['$month' => '$created_at']
                        ],
                        'tours_scheduled' => ['$sum' => 1],
                        'tours_completed' => [
                            '$sum' => [
                                '$cond' => [
                                    ['$eq' => ['$status', 'completed']],
                                    1,
                                    0
                                ]
                            ]
                        ]
                    ]
                ],
                ['$sort' => ['_id' => 1]]
            ];
            
            $toursCursor = $this->toursCollection->aggregate($toursPipeline);
            $toursResults = iterator_to_array($toursCursor);
            
            return [
                'properties' => $propertiesResults,
                'tours' => $toursResults
            ];
            
        } catch (Exception $e) {
            error_log('Error getting agent performance metrics: ' . $e->getMessage());
            return [
                'properties' => [],
                'tours' => []
            ];
        }
    }

    /**
     * Get agent performance statistics
     */
    private function getAgentPerformanceStats($agentId) {
        $metrics = $this->getPerformanceMetrics($agentId, '30days');
        
        $totalListed = 0;
        $totalSold = 0;
        $totalTours = 0;
        $completedTours = 0;
        
        foreach ($metrics['properties'] as $property) {
            $totalListed += $property['properties_listed'];
            $totalSold += $property['properties_sold'];
        }
        
        foreach ($metrics['tours'] as $tour) {
            $totalTours += $tour['tours_scheduled'];
            $completedTours += $tour['tours_completed'];
        }
        
        return [
            'properties_listed' => $totalListed,
            'properties_sold' => $totalSold,
            'conversion_rate' => $totalListed > 0 ? round(($totalSold / $totalListed) * 100, 2) : 0,
            'tours_scheduled' => $totalTours,
            'tours_completed' => $completedTours,
            'tour_completion_rate' => $totalTours > 0 ? round(($completedTours / $totalTours) * 100, 2) : 0
        ];
    }

    /**
     * Schedule a property tour
     */
    public function schedulePropertyTour($propertyId, $agentId, $data) {
        try {
            $tourData = [
                'property_id' => $propertyId,
                'agent_id' => $agentId,
                'client_name' => $data['client_name'],
                'client_email' => $data['client_email'],
                'client_phone' => $data['client_phone'] ?? '',
                'scheduled_date' => new MongoDB\BSON\UTCDateTime(strtotime($data['scheduled_date']) * 1000),
                'scheduled_time' => $data['scheduled_time'],
                'notes' => $data['notes'] ?? '',
                'status' => 'pending',
                'created_at' => new MongoDB\BSON\UTCDateTime(),
                'updated_at' => new MongoDB\BSON\UTCDateTime()
            ];

            $result = $this->toursCollection->insertOne($tourData);
            
            if ($result->getInsertedCount() > 0) {
                $tourData['id'] = (string)$result->getInsertedId();
                unset($tourData['_id']);
                return $tourData;
            }
            
            return false;
            
        } catch (Exception $e) {
            error_log('Error scheduling property tour: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Update tour status
     */
    public function updateTourStatus($tourId, $status) {
        try {
            $result = $this->toursCollection->updateOne(
                ['_id' => new MongoDB\BSON\ObjectId($tourId)],
                [
                    '$set' => [
                        'status' => $status,
                        'updated_at' => new MongoDB\BSON\UTCDateTime()
                    ]
                ]
            );
            
            return $result->getModifiedCount() > 0;
            
        } catch (Exception $e) {
            error_log('Error updating tour status: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Get recent activities for agent
     */
    private function getRecentActivities($agentId, $limit = 10) {
        try {
            $activities = [];
            
            // Recent properties
            $recentProperties = $this->propertiesCollection->find(
                ['agent_id' => $agentId],
                [
                    'sort' => ['created_at' => -1],
                    'limit' => $limit / 2,
                    'projection' => ['title' => 1, 'status' => 1, 'created_at' => 1],
                    'typeMap' => ['root' => 'array', 'document' => 'array']
                ]
            );
            
            foreach ($recentProperties as $property) {
                $activities[] = [
                    'type' => 'property',
                    'action' => 'listed',
                    'message' => "Listed property: {$property['title']}",
                    'timestamp' => $property['created_at']
                ];
            }
            
            // Recent tours
            $recentTours = $this->toursCollection->find(
                ['agent_id' => $agentId],
                [
                    'sort' => ['created_at' => -1],
                    'limit' => $limit / 2,
                    'projection' => ['client_name' => 1, 'status' => 1, 'created_at' => 1],
                    'typeMap' => ['root' => 'array', 'document' => 'array']
                ]
            );
            
            foreach ($recentTours as $tour) {
                $activities[] = [
                    'type' => 'tour',
                    'action' => 'scheduled',
                    'message' => "Scheduled tour with {$tour['client_name']}",
                    'timestamp' => $tour['created_at']
                ];
            }
            
            // Sort activities by timestamp
            usort($activities, function($a, $b) {
                return $b['timestamp'] <=> $a['timestamp'];
            });
            
            return array_slice($activities, 0, $limit);
            
        } catch (Exception $e) {
            error_log('Error getting recent activities: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get all agents
     */
    public function getAllAgents($page = 1, $limit = 10) {
        try {
            $filter = ['role' => 'agent'];
            
            $options = [
                'skip' => ($page - 1) * $limit,
                'limit' => $limit,
                'sort' => ['created_at' => -1],
                'projection' => ['password' => 0],
                'typeMap' => ['root' => 'array', 'document' => 'array']
            ];
            
            $cursor = $this->usersCollection->find($filter, $options);
            $agents = iterator_to_array($cursor);
            
            // Convert ObjectId to string and add stats
            foreach ($agents as &$agent) {
                $agent['id'] = (string)$agent['_id'];
                unset($agent['_id']);
                
                // Add basic stats
                $agent['properties_count'] = $this->propertiesCollection->countDocuments(['agent_id' => $agent['id']]);
                $agent['tours_count'] = $this->toursCollection->countDocuments(['agent_id' => $agent['id']]);
            }
            
            // Get total count for pagination
            $total = $this->usersCollection->countDocuments($filter);
            
            return [
                'data' => $agents,
                'pagination' => [
                    'total' => $total,
                    'page' => $page,
                    'limit' => $limit,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log('Error getting all agents: ' . $e->getMessage());
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
}
