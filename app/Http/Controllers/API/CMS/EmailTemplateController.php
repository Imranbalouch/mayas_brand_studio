<?php

namespace App\Http\Controllers\API\CMS;

use App\Models\CMS\EmailTemplate;
use App\Traits\MessageTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

class EmailTemplateController extends Controller
{
    use MessageTrait;

    public function index(Request $request)
    {
        try {
            $templates = EmailTemplate::query();

            $templates = $templates->orderBy('created_at', 'desc')->get();

            return response()->json([
                'status_code' => 200,
                'data' => $templates
            ], 200);
        } catch (\Exception $e) {
            Log::error('Email Template List Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => Response::HTTP_INTERNAL_SERVER_ERROR,
                'message' => $this->get_message('server_error'),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    public function store(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_slug' => ['required', 'string', 'max:255'],
                'template_title' => ['required', 'string', 'max:255', 'unique:email_templates,template_title'],
                'subject' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'type' => ['required', 'string', 'in:career'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::beginTransaction();

            $template = EmailTemplate::create([
                'template_slug' => $request->template_slug,
                'template_title' => $request->template_title,
                'subject' => $request->subject,
                'form_name' => $request->form_name,
                'description' => $request->description,
                'body' => $request->body,
                'send_as_plaintext' => $request->send_as_plaintext ?? false,
                'disabled' => $request->disabled ?? false,
                'type' => $request->type,
            ]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => 'Email template created successfully',
                'data' => $template
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Email Template Store Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    public function edit(string $uuid)
    {
        try {
            $template = EmailTemplate::findByUuid($uuid);
            
            return response()->json([
                'status_code' => 200,
                'data' => $template,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Email Template Edit Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 404,
                'message' => 'Email template not found',
            ], 404);
        }
    }

    public function update(Request $request, string $uuid)
    {
        try {
            $validator = Validator::make($request->all(), [
                'template_slug' => ['required', 'string', 'max:255'],
                'template_title' => ['required', 'string', 'max:255', 'unique:email_templates,template_title,' . $uuid . ',uuid'],
                'subject' => ['required', 'string', 'max:255'],
                'body' => ['required', 'string'],
                'type' => ['required', 'string', 'in:career'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status_code' => Response::HTTP_UNPROCESSABLE_ENTITY,
                    'errors' => $validator->errors()
                ], Response::HTTP_UNPROCESSABLE_ENTITY);
            }

            DB::beginTransaction();

            $template = EmailTemplate::findByUuid($uuid);
            $template->update([
                'template_slug' => $request->template_slug,
                'template_title' => $request->template_title,
                'subject' => $request->subject,
                'form_name' => $request->form_name,
                'description' => $request->description,
                'body' => $request->body,
                'send_as_plaintext' => $request->send_as_plaintext ?? false,
                'disabled' => $request->disabled ?? false,
                'type' => $request->type,
            ]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => 'Email template updated successfully',
                'data' => $template
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Email Template Update Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    public function destroy(string $uuid)
    {
        try {
            $template = EmailTemplate::findByUuid($uuid);
            $template->delete();

            return response()->json([
                'status_code' => 200,
                'message' => 'Email template deleted successfully',
            ], 200);
        } catch (\Exception $e) {
            Log::error('Email Template Delete Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    public function toggleStatus(string $uuid)
    {
        try {
            $template = EmailTemplate::findByUuid($uuid);
            $template->update(['disabled' => !$template->disabled]);

            return response()->json([
                'status_code' => 200,
                'message' => 'Email template status updated successfully',
                'data' => $template
            ], 200);
        } catch (\Exception $e) {
            Log::error('Email Template Toggle Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Get merge fields for a specific template type
     */
    public function getMergeFields(Request $request)
    {
        $type = $request->type ?? 'lead';
        
        $mergeFields = [
            'career' => [
                'Careers' => [
                    'career_name'                         => 'Career Name',
                    'career_slug'                         => 'Career Slug',
                    'career_short_description'            => 'Career Short Description',
                    'career_job_location'                 => 'Career Job Location',
                    'career_job_department'               => 'Career Job Department',
                    'career_employment_status'            => 'Career Employment Status',
                    'career_employment_type'              => 'Career Employment Type',
                    'career_number_of_vacancies'          => 'Career Number of Vacancies',
                    'career_posted_date'                  => 'Career Posted Date',
                    'career_detail_job_description_title' => 'Career Detail Job Description Title',
                    'career_job_description'              => 'Career Job Description',
                    'career_job_requirement_title'        => 'Career Job Requirement Title',
                    'career_job_requirement'              => 'Career Job Requirement',
                    'career_meta_title'                   => 'Career Meta Title',
                    'career_meta_description'             => 'Career Meta Description',
                    'career_status'                       => 'Career Status',
                    'career_applications_name'            => 'Career Applications Name',
                    'career_applications_phone'           => 'Career Applications Phone',
                    'career_applications_email'           => 'Career Applications Email',
                    'career_applications_cv'              => 'Career Applications CV',
                    'career_applications_cover_letter'    => 'Career Applications Cover Letter',
                ],
                'Other' => [
                    'logo_url' => 'Logo URL',
                    'theme_logo_url' => 'Theme Logo URL',
                    'theme_logo' => 'Theme Logo',
                    'logo_image_with_url' => 'Logo Image with URL',
                    'dark_logo_image_with_url' => 'Dark logo image with URL',
                    'cms_url' => 'CMS URL',
                    'admin_url' => 'Admin URL',
                    'main_domain' => 'Main Domain',
                    'companyname' => 'Company Name',
                    'year' => 'Year',
                    'terms_and_conditions_url' => '(GDPR) Terms & Conditions URL',
                    'privacy_policy_url' => '(GDPR) Privacy Policy URL'
                ]
            ]
        ];

        return response()->json([
            'status_code' => 200,
            'data' => $mergeFields[$type] ?? []
        ], 200);
    }

     public function deleteAll()
    {
        try {
            DB::beginTransaction();
            
            $templatesCount = EmailTemplate::count();
            if ($templatesCount === 0) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No email templates found to delete',
                ], 404);
            }

            EmailTemplate::query()->delete();

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => 'All email templates (' . $templatesCount . ') deleted successfully',
                'deleted_count' => $templatesCount
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Email Template Delete All Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Disable all email templates
     */
    public function disableAll()
    {
        try {
            DB::beginTransaction();

            $templatesCount = EmailTemplate::count();
            
            if ($templatesCount === 0) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No email templates found to disable',
                ], 404);
            }

            $updatedCount = EmailTemplate::where('disabled', false)->update(['disabled' => true]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => 'All email templates (' . $updatedCount . ') disabled successfully',
                'updated_count' => $updatedCount
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Email Template Disable All Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }

    /**
     * Enable all email templates
     */
    public function enableAll()
    {
        try {
            DB::beginTransaction();

            $templatesCount = EmailTemplate::count();
            
            if ($templatesCount === 0) {
                return response()->json([
                    'status_code' => 404,
                    'message' => 'No email templates found to enable',
                ], 404);
            }

            $updatedCount = EmailTemplate::where('disabled', true)->update(['disabled' => false]);

            DB::commit();

            return response()->json([
                'status_code' => 200,
                'message' => 'All email templates (' . $updatedCount . ') enabled successfully',
                'updated_count' => $updatedCount
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Email Template Enable All Error: ' . $e->getMessage());
            return response()->json([
                'status_code' => 500,
                'message' => $this->get_message('server_error'),
            ], 500);
        }
    }
}
