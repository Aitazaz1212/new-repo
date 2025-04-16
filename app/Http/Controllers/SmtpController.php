<?php

namespace App\Http\Controllers;

use App\Models\SmtpSetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use App\Mail\TestSmtp;
use Illuminate\Support\Facades\Mail;

/**
 * @tags SMTP Settings Management
 */
class SmtpController extends Controller
{
    /**
     * Create or Update SMTP Settings
     * 
     * Create or update SMTP server configuration.
     * 
     * @group SMTP Settings Management
     * 
     * @bodyParam id integer nullable The ID of the SMTP setting to update. Example: 1
     * @bodyParam smtp string required SMTP server address. Example: "smtp.gmail.com"
     * @bodyParam email string required Email address. Example: "example@gmail.com"
     * @bodyParam name string required Account name. Example: "John Doe"
     * @bodyParam pwd string required SMTP password. Example: "password123"
     * @bodyParam tls_one boolean required Enable TLS. Example: true
     * @bodyParam outName string required Sender name. Example: "Company Name"
     * @bodyParam ssl_one string required Enable SSL (yes/no). Example: "yes"
     * @bodyParam port string required SMTP port. Example: "587"
     * @bodyParam company_id integer nullable Company ID. Example: 1
     * 
     * @response {
     *   "data": {
     *     "id": 1,
     *     "smtp": "smtp.gmail.com",
     *     "email": "example@gmail.com",
     *     "name": "John Doe",
     *     "tls": true,
     *     "out_name": "Company Name",
     *     "ssl_enabled": "yes",
     *     "port": "587",
     *     "company_id": 1,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     */
    public function createOrUpdate(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'id' => 'nullable|exists:smtp_settings,id',
            'smtp' => 'required|string|max:400',
            'email' => 'required|string|email|max:500',
            'name' => 'required|string|max:100',
            'pwd' => 'required|string|max:500',
            'tls_one' => 'required|boolean',
            'outName' => 'nullable|string|max:255',
            'ssl_one' => 'required|in:yes,no',
            'port' => 'required|string',
            'company_id' => 'nullable|exists:companies,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {


            $smtpSetting = SmtpSetting::updateOrCreate(
                ['id' => $request->id],
                [
                    'smtp' => $request->smtp,
                    'email' => $request->email,
                    'name' => $request->name,
                    'pwd' => $request->pwd,
                    'tls' => $request->tls_one,
                    'out_name' => $request->outName,
                    'ssl_enabled' => $request->ssl_one,
                    'port' => $request->port,
                    'company_id' => $request->company_id
                ]
            );



            return response()->json([
                'data' => $smtpSetting,
                'success' => true
            ], $request->id ? 200 : 201);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Failed to save SMTP settings',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Get SMTP Email Settings
     * 
     * Retrieve the current SMTP email configuration.
     * 
     * @group SMTP Settings Management
     * 
     * @response {
     *   "data": {
     *     "id": 1,
     *     "smtp": "smtp.gmail.com",
     *     "email": "example@gmail.com",
     *     "name": "John Doe",
     *     "tls": true,
     *     "out_name": "Company Name",
     *     "ssl_enabled": "yes",
     *     "port": "587",
     *     "company_id": 1,
     *     "created_at": "2024-03-20T12:00:00Z",
     *     "updated_at": "2024-03-20T12:00:00Z"
     *   },
     *   "success": true
     * }
     * 
     * @response 404 {
     *   "message": "SMTP settings not found",
     *   "success": false
     * }
     */
    public function getSmtpEmail(): JsonResponse
    {
        try {
            $smtpSettings = SmtpSetting::first();

            if (!$smtpSettings) {
                return response()->json([
                    'message' => 'SMTP settings not found',
                    'success' => false,
                    'success' => false
                ], 200);
            }

            return response()->json([
                'data' => $smtpSettings,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to fetch SMTP settings',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }

    /**
     * Send Test Email
     * 
     * Send a test email using the configured SMTP settings.
     * 
     * @group SMTP Settings Management
     * 
     * @bodyParam smtpEmail string required The sender email address. Example: "sender@example.com"
     * @bodyParam outgoingEmail string required The recipient email address. Example: "recipient@example.com"
     * 
     * @response {
     *   "status": "Email sent from sender@example.com to recipient@example.com",
     *   "success": true
     * }
     * 
     * @response 422 {
     *   "message": "Validation failed",
     *   "errors": {
     *     "smtpEmail": ["The smtp email field is required."]
     *   },
     *   "success": false
     * }
     * 
     * @response 500 {
     *   "message": "Failed to send email",
     *   "error": "SMTP connection failed",
     *   "success": false
     * }
     */
    public function sendEmail(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'smtpEmail' => 'required|email|max:500',
            'outgoingEmail' => 'required|email|max:500'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
                'success' => false
            ], 422);
        }

        try {
            $smtpEmail = $request->smtpEmail;
            $outgoingEmail = $request->outgoingEmail;

            Mail::to($outgoingEmail)->send(new TestSmtp($outgoingEmail, $smtpEmail));

            return response()->json([
                'status' => 'Email sent from ' . $smtpEmail . ' to ' . $outgoingEmail,
                'success' => true
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Failed to send email',
                'error' => $e->getMessage(),
                'success' => false
            ], 500);
        }
    }
}
