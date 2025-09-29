<?php
require_once __DIR__ . '/BaseController.php';
require_once __DIR__ . '/../models/AgentApplication.php';
require_once __DIR__ . '/../helpers/Jwt.php';

class AgentController extends BaseController {
    private $agentApplication;

    public function __construct() {
        $this->agentApplication = new AgentApplication();
    }

    public function applyForAgent() {
        $data = $this->getJsonBody();

        // Validate required fields
        $requiredFields = [
            'first_name', 'last_name', 'email', 'username', 'phone',
            'address', 'city', 'state', 'zip_code', 'date_of_birth',
            'gender', 'bank', 'account_number', 'account_name', 'id_document'
        ];

        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                $this->errorResponse("$field is required", 400);
            }
        }

        // Save application
        $result = $this->agentApplication->create($data);

        if ($result) {
            $this->successResponse('Agent application submitted successfully', $result, 201);
        } else {
            $this->errorResponse('Failed to submit agent application', 500);
        }
    }

    public function getAllApplications() {
        $applications = $this->agentApplication->getAll();
        $this->successResponse('Applications retrieved successfully', $applications);
    }

    public function getApplicationById($id) {
        $application = $this->agentApplication->getById($id);

        if ($application) {
            $this->successResponse('Application found', $application);
        } else {
            $this->errorResponse('Application not found', 404);
        }
    }

    public function updateApplicationStatus($id) {
        $data = $this->getJsonBody();

        if (empty($data['status'])) {
            $this->errorResponse('Status is required', 400);
        }

        $result = $this->agentApplication->updateStatus($id, $data['status']);

        if ($result) {
            $this->successResponse('Application status updated successfully');
        } else {
            $this->errorResponse('Failed to update application status', 500);
        }
    }

    public function deleteApplication($id) {
        $application = $this->agentApplication->getById($id);

        if (!$application) {
            $this->errorResponse('Application not found', 404);
        }

        $result = $this->agentApplication->delete($id);

        if ($result) {
            $this->successResponse('Application deleted successfully');
        } else {
            $this->errorResponse('Failed to delete application', 500);
        }
    }

    // Dashboard methods
    public function getDashboard() {
        $this->successResponse('Agent dashboard data', [
            'message' => 'Dashboard endpoint - implement as needed'
        ]);
    }

    public function getAgentProperties() {
        $this->successResponse('Agent properties', [
            'message' => 'Agent properties endpoint - implement as needed'
        ]);
    }

    public function getScheduledTours() {
        $this->successResponse('Scheduled tours', [
            'message' => 'Scheduled tours endpoint - implement as needed'
        ]);
    }

    public function getEarnings() {
        $this->successResponse('Agent earnings', [
            'message' => 'Earnings endpoint - implement as needed'
        ]);
    }

    public function getPerformanceMetrics() {
        $this->successResponse('Performance metrics', [
            'message' => 'Performance metrics endpoint - implement as needed'
        ]);
    }

    // Property management methods
    public function createProperty() {
        $this->successResponse('Property created', [
            'message' => 'Create property endpoint - implement as needed'
        ]);
    }

    public function updateProperty($id) {
        $this->successResponse('Property updated', [
            'id' => $id,
            'message' => 'Update property endpoint - implement as needed'
        ]);
    }

    public function deleteProperty($id) {
        $this->successResponse('Property deleted', [
            'id' => $id,
            'message' => 'Delete property endpoint - implement as needed'
        ]);
    }

    public function schedulePropertyTour($id) {
        $this->successResponse('Tour scheduled', [
            'property_id' => $id,
            'message' => 'Schedule tour endpoint - implement as needed'
        ]);
    }

}
