<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Language strings for local_chronifyai
 *
 * @package    local_chronifyai
 * @copyright  2025 SEBALE Innovations (http://sebale.net)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

$string['chronifyai:useservice'] = 'ChronifyAI API: use service';
$string['error:api:communicationfailed'] = 'An error occurred while communicating with the ChronifyAI API: {$a}';
$string['error:api:invalidconfig'] = 'Invalid configuration';
$string['error:auth:approvalinstruction'] = 'Please approve this site connection in your ChronifyAI App to continue.';
$string['error:auth:approvalrequired'] = 'Approval Required';
$string['error:auth:clientidmissing'] = 'Client ID is missing. Please configure it in the plugin settings.';
$string['error:auth:clientsecretmissing'] = 'Client Secret is missing. Please configure it in the plugin settings.';
$string['error:auth:emptytokenresponse'] = 'Auth response returned no token';
$string['error:auth:failed'] = 'Authentication failed';
$string['error:auth:tokenlockfailed'] = 'Token lock failed';
$string['error:backup:cannotcopyfile'] = 'Cannot copy backup file to temporary directory';
$string['error:backup:cannotcreatetempdirectory'] = 'Cannot create temporary directory for backup files';
$string['error:backup:controllerfailed'] = 'Failed to create backup controller: {$a}';
$string['error:backup:downloadfailed'] = 'Failed to download backup file from ChronifyAI: {$a}';
$string['error:backup:extractionfailed'] = 'Failed to extract backup file: {$a}';
$string['error:backup:failed'] = 'Backup process failed';
$string['error:backup:filenotfound'] = 'Backup file not found: {$a}';
$string['error:backup:filenotreadable'] = 'Backup file is not readable: {$a}';
$string['error:backup:initiationfailed'] = 'Failed to initiate course backup';
$string['error:backup:invalidfile'] = 'Invalid backup file: {$a}';
$string['error:backup:invalidstructure'] = 'Invalid backup structure: {$a}';
$string['error:backup:uploadfailed'] = 'Failed to upload backup file to ChronifyAI';
$string['error:course:creationfailed'] = 'Failed to create new course: {$a}';
$string['error:course:notsupported'] = 'Site course is not supported';
$string['error:course:overridefailed'] = 'Failed to apply course parameter overrides: {$a}';
$string['error:dir:createfailed'] = 'Directory creation failed: {$a}';
$string['error:export:cannotcreatedirectory'] = 'Cannot create export directory: {$a}';
$string['error:export:cannotcreatefile'] = 'Cannot create export file: {$a}';
$string['error:export:invaliddata'] = 'Invalid data structure for export: {$a}';
$string['error:export:jsonencodefailed'] = 'JSON encoding failed: {$a}';
$string['error:export:writefailed'] = 'Failed to write to export file: {$a}';
$string['error:file:downloadfailed'] = 'File download failed: {$a}';
$string['error:file:notfound'] = 'File not found: {$a}';
$string['error:file:readfailed'] = 'File read failed: {$a}';
$string['error:file:sizefailed'] = 'File size failed: {$a}';
$string['error:file:uploadfailed'] = 'File upload failed: {$a}';
$string['error:permission:nopermission'] = 'You do not have permission to perform this action in the LMS';
$string['error:report:cannotcreatefile'] = 'Cannot create report file in temporary directory';
$string['error:report:failed'] = 'Report generation process failed';
$string['error:report:uploadfailed'] = 'Failed to upload report to ChronifyAI';
$string['error:restore:controllerfailed'] = 'Failed to create restore controller: {$a}';
$string['error:restore:error'] = 'Restore error: {$a}';
$string['error:restore:executionfailed'] = 'Restore execution failed: {$a}';
$string['error:restore:failed'] = 'Restore process failed';
$string['error:restore:precheckfailed'] = 'Restore precheck failed: {$a}';
$string['error:transcripts:export:emptyfile'] = 'No valid transcript data was generated for export.';
$string['error:transcripts:export:failed'] = 'Failed to export transcripts. Please try again later.';
$string['error:user:idmissing'] = 'User ID is required for restore operations';
$string['error:user:notfound'] = 'User not found: {$a}';
$string['error:validation:categoryidrequired'] = 'Category ID is required for new course creation';
$string['error:validation:categorynotfound'] = 'Category not found';
$string['error:validation:endbeforestart'] = 'End date must be after start date';
$string['error:validation:enddaterequiresstartdate'] = 'End date cannot be set without a start date';
$string['error:validation:fullnamerequired'] = 'Course full name is required';
$string['error:validation:fullnametoolong'] = 'Full name is too long (maximum 254 characters)';
$string['error:validation:invalidcategoryid'] = 'Category ID must be a positive number';
$string['error:validation:invalidenddate'] = 'End date must be a valid timestamp';
$string['error:validation:invalidfullname'] = 'Full name must be a string';
$string['error:validation:invalidjson'] = 'Invalid JSON format: {$a}';
$string['error:validation:invalidrestoreoptions'] = 'Invalid restore options';
$string['error:validation:invalidshortname'] = 'Short name must be a string';
$string['error:validation:invalidstartdate'] = 'Start date must be a valid timestamp';
$string['error:validation:invalidvisible'] = 'Visible must be 0 or 1';
$string['error:validation:jsonnotobject'] = 'JSON must be an object';
$string['error:validation:shortnameexists'] = 'A course with this short name already exists';
$string['error:validation:shortnamerequired'] = 'Course short name is required';
$string['error:validation:shortnametoolong'] = 'Short name is too long (maximum 255 characters)';
$string['notification:course:backup:completed:message'] = 'Course backup has been completed for: {$a->coursename}.';
$string['notification:course:backup:completed:title'] = 'Course Backup Complete';
$string['notification:course:restore:completed:message'] = 'Course restore has been completed for: {$a->coursename}. View course: <a href="{$a->courseurl}" target="_blank" style="color: blue;">LINK</a>';
$string['notification:course:restore:completed:title'] = 'Course Restore Complete';
$string['notification:course:restore:failed:message'] = 'Course restore failed. Please check the system logs for detailed information.';
$string['notification:course:restore:failed:title'] = 'Course Restore Failed';
$string['notification:course:restore:failed:withname:message'] = 'Course restore failed for: {$a}. Please check the system logs for detailed information.';
$string['notification:transcripts:completed:message'] = 'All user transcripts have been successfully exported to ChronifyAI.';
$string['notification:transcripts:completed:title'] = 'Transcripts Export Completed';
$string['notification:transcripts:failed:message'] = 'Transcripts export failed: {$a}';
$string['notification:transcripts:failed:title'] = 'Transcripts Export Failed';
$string['pluginname'] = 'ChronifyAI';
$string['privacy:metadata'] = 'The ChronifyAI plugin stores API credentials in site configuration and transmits course data to the ChronifyAI external service for archiving purposes.';
$string['privacy:metadata:chronifyaiservice'] = 'Course data and metadata is transmitted to the ChronifyAI external service for archiving and compliance purposes. User information is included only when users interact with archived courses.';
$string['privacy:metadata:chronifyaiservice:coursecategory'] = 'The category the course belongs to';
$string['privacy:metadata:chronifyaiservice:courseid'] = 'The Moodle course ID';
$string['privacy:metadata:chronifyaiservice:coursename'] = 'The full name of the course';
$string['privacy:metadata:chronifyaiservice:courseshortname'] = 'The short name of the course';
$string['privacy:metadata:chronifyaiservice:userid'] = 'The Moodle user ID of users enrolled in or accessing the course';
$string['privacy:metadata:chronifyaiservice:username'] = 'The user\'s username';
$string['privacy:metadata:chronifyaiservice:useremail'] = 'The email address used for SSO authentication with ChronifyAI';
$string['privacy:metadata:chronifyaiservice:userfirstname'] = 'The user\'s first name';
$string['privacy:metadata:chronifyaiservice:userlastname'] = 'The user\'s last name';
$string['privacy:metadata:chronifyaiservice:activities'] = 'Course activities and their configurations';
$string['privacy:metadata:chronifyaiservice:assignments'] = 'Assignment details and settings';
$string['privacy:metadata:chronifyaiservice:quizzes'] = 'Quiz questions, settings, and configurations';
$string['privacy:metadata:chronifyaiservice:forums'] = 'Forum discussions and topics';
$string['privacy:metadata:chronifyaiservice:files'] = 'Course files and resources uploaded by teachers and students';
$string['privacy:metadata:chronifyaiservice:grades'] = 'Student grades for all graded activities';
$string['privacy:metadata:chronifyaiservice:submissions'] = 'Student assignment submissions and feedback';
$string['privacy:metadata:chronifyaiservice:quizattempts'] = 'Student quiz attempts, answers, and scores';
$string['privacy:metadata:chronifyaiservice:forumposts'] = 'Student forum posts and discussions';
$string['privacy:metadata:chronifyaiservice:completiondata'] = 'Course and activity completion status';
$string['privacy:metadata:chronifyaiservice:transcriptgrades'] = 'Complete transcript of all student grades';
$string['privacy:metadata:chronifyaiservice:coursecompletions'] = 'Course completion dates and certificates';
$string['privacy:metadata:chronifyaiservice:certificatedata'] = 'Issued certificates and achievement data';
$string['privacy:metadata:configplugins'] = 'The plugin stores API authentication credentials (Client ID and Client Secret) in Moodle\'s configuration table. These credentials are stored as plain text in the database and are only accessible to site administrators.';
$string['privacy:metadata:configplugins:name'] = 'The configuration setting name (e.g., client_id, client_secret, api_base_url)';
$string['privacy:metadata:configplugins:plugin'] = 'The plugin name (local_chronifyai)';
$string['privacy:metadata:configplugins:value'] = 'The configuration setting value, including API credentials stored as plain text';
$string['settings:api:apibaseurl'] = 'API Base URL';
$string['settings:api:apibaseurl_desc'] = 'The base URL for the ChronifyAI API.';
$string['settings:api:apibaseurl_help'] = 'Enter the full URL of the ChronifyAI API endpoint. This is typically provided in your ChronifyAI account dashboard.';
$string['settings:api:email'] = 'API User Email';
$string['settings:api:email_desc'] = 'Email address used for API authentication. If left empty, the current user\'s email will be used. Must be the same as the ChronifyAI account email.';
$string['settings:authentication:clientid'] = 'Client ID';
$string['settings:authentication:clientid_desc'] = 'Your ChronifyAI Client ID. Get this from your ChronifyAI account dashboard.';
$string['settings:authentication:clientid_help'] = 'The unique identifier for your ChronifyAI API application. This is used to authenticate requests to the ChronifyAI API.';
$string['settings:authentication:clientsecret'] = 'Client Secret';
$string['settings:authentication:clientsecret_desc'] = 'Your ChronifyAI Client Secret. Get this from your ChronifyAI account dashboard.';
$string['settings:authentication:clientsecret_help'] = 'The secret key for your ChronifyAI API application. Keep this secure and never share it publicly.';
$string['settings:features:enabled'] = 'Enable ChronifyAI';
$string['settings:features:enabled_desc'] = 'Enable or disable the ChronifyAI plugin functionality';
$string['settings:features:enabled_help'] = 'When enabled, ChronifyAI will automatically archive your courses and student transcripts to ensure compliance and data preservation.';
$string['status:backup:completed'] = 'Backup completed successfully';
$string['status:backup:initiated'] = 'Course backup has been initiated';
$string['status:backup:inprogress'] = 'Backup is currently in progress';
$string['status:backup:started'] = 'Backup process has started';
$string['status:backup:uploadsuccess'] = 'Backup file successfully uploaded to ChronifyAI';
$string['status:connection:successful'] = 'Connection successful!';
$string['status:course:defaultname'] = 'Restored Course {$a}';
$string['status:course:found'] = 'Course found';
$string['status:course:notfound'] = 'Course not found';
$string['status:plugin:disabled'] = 'The ChronifyAI plugin is currently disabled. Please enable it in the plugin settings.';
$string['status:report:started'] = 'Course report generation has started';
$string['status:restore:started'] = 'Course restore process has started';
$string['status:settings:saved'] = 'Settings saved successfully';
$string['task:backupandupload'] = 'ChronifyAI: Backup and Upload Course';
$string['task:generateanduploadreport'] = 'ChronifyAI: Generate and Upload Course Report';
$string['task:restorecourse'] = 'ChronifyAI: Download and Restore Course';
$string['task:transcripts:export'] = 'ChronifyAI: Export User Transcripts';
$string['transcripts:export:completed'] = 'All user transcripts have been successfully exported to ChronifyAI.';
$string['transcripts:export:queued'] = 'Transcripts export has been queued and will be processed shortly.';
$string['ui:button:dashboard'] = 'Go to Dashboard';
$string['ui:button:save'] = 'Save';
$string['ui:button:saveandcomplete'] = 'Save and Complete Setup';
$string['ui:button:saveandnext'] = 'Save and Next';
$string['ui:button:settings'] = 'Manage Settings';
$string['ui:button:testconnection'] = 'Test API Connection';
$string['ui:label:disabled'] = 'Disabled';
$string['ui:label:enabled'] = 'Enabled';
$string['wizard:benefits:automatedarchiving'] = 'Automated course and transcript archiving';
$string['wizard:benefits:complianceready'] = 'Compliance-ready data storage';
$string['wizard:benefits:easyretrieval'] = 'Easy search and retrieval for audits';
$string['wizard:benefits:securestorage'] = 'Secure, encrypted cloud storage';
$string['wizard:benefits:title'] = 'What you get with ChronifyAI:';
$string['wizard:common:background:alt'] = 'ChronifyAI Features Slides';
$string['wizard:common:copyright'] = '©2025 ChronifyAI, Inc. All Rights Reserved.';
$string['wizard:common:title'] = 'ChronifyAI Setup Wizard';
$string['wizard:step1:description'] = 'We help course creators and administrators automatically store courses and learner transcripts, ensuring long-term compliance and easy access for audits - without the clutter, risk, or manual effort of traditional backups.<br><br>Stay organized, compliant, and in control with just a few clicks.';
$string['wizard:step1:letssetup'] = 'Let\'s Set It Up';
$string['wizard:step1:maintitle'] = 'Next-Gen<br><span class="chronifyai-highlight">Data Archiving</span><br>for Your LMS!';
$string['wizard:step1:nav'] = 'Welcome';
$string['wizard:step1:title'] = 'Next-Gen Data Archiving for Your LMS!';
$string['wizard:step2:description'] = 'Enter your API credentials to connect with Chronify';
$string['wizard:step2:info'] = 'Your credentials are encrypted and stored securely. You can find them in your ChronifyAI dashboard under Settings → API Keys.';
$string['wizard:step2:nav'] = 'Authentication';
$string['wizard:step2:test:button'] = 'Test Connection';
$string['wizard:step2:test:description'] = 'Verify your API credentials are working correctly before proceeding.';
$string['wizard:step2:test:title'] = 'Test API Connection';
$string['wizard:step2:title'] = 'Setting up your Chronify';
$string['wizard:step3:description'] = 'Enable ChronifyAI features to start archiving your course data';
$string['wizard:step3:feature:backup:desc'] = 'Automatically archive course content and structure';
$string['wizard:step3:feature:backup:title'] = 'Course Backup';
$string['wizard:step3:feature:restore:desc'] = 'Restore archived courses back to Moodle';
$string['wizard:step3:feature:restore:title'] = 'Course Restore';
$string['wizard:step3:feature:transcripts:desc'] = 'Export and archive student transcripts';
$string['wizard:step3:feature:transcripts:title'] = 'Transcript Export';
$string['wizard:step3:nav'] = 'Features';
$string['wizard:step3:title'] = 'Enable Features';
$string['wizard:step4:capability1'] = 'Archive courses with a single click';
$string['wizard:step4:capability2'] = 'Preserve learner transcripts and performance data';
$string['wizard:step4:capability3'] = 'Stay compliant and always audit-ready';
$string['wizard:step4:capability4'] = 'Access your archived courses and transcripts anytime via the Chronify platform';
$string['wizard:step4:capability:intro'] = 'You can now';
$string['wizard:step4:dashboard'] = 'Open ChronifyAI Dashboard';
$string['wizard:step4:description'] = 'Congratulations! Your Moodle site is now successfully connected to Chronify.';
$string['wizard:step4:message'] = 'Your ChronifyAI integration is complete and ready to use!';
$string['wizard:step4:nav'] = 'Complete';
$string['wizard:step4:nextstep'] = 'Next Step';
$string['wizard:step4:nextstep:description'] = 'Head to your Chronify dashboard to begin archiving and managing your LMS data.';
$string['wizard:step4:nextsteps'] = 'Next Steps';
$string['wizard:step4:settings'] = 'Manage Settings';
$string['wizard:step4:step1'] = 'Configure your backup schedules in the ChronifyAI dashboard';
$string['wizard:step4:step2'] = 'Set up automated archiving rules for your courses';
$string['wizard:step4:step3'] = 'Review privacy and compliance settings';
$string['wizard:step4:support:text'] = 'Need help? Contact us at';
$string['wizard:step4:title'] = 'Setup Complete and You\'re Connected!';
$string['connection:test:allfieldsrequired'] = 'All fields are required for connection testing.';
$string['connection:test:invalidurlformat'] = 'Invalid API URL format. Please provide a valid URL.';
$string['connection:test:success'] = 'Connection successful! Authentication and API access verified.';
$string['connection:test:configerror'] = 'Configuration error. Please verify all settings are correct.';
$string['connection:test:authfailed'] = 'Authentication failed. Please check your Client ID and Client Secret.';
$string['connection:test:approvalrequired'] = 'Approval required. Please check your email and approve the integration in ChronifyAI admin panel.';
$string['connection:test:networkerror'] = 'Network error. Please check the API URL and your internet connection.';
$string['wizard:dashboard:url'] = 'https://app.chronifyai.com'; // Default ChronifyAI dashboard URL.
$string['warning:externaldatatransmission'] = '⚠️ <strong>Data Transmission Notice:</strong> ChronifyAI transmits course data including student information to external servers at ChronifyAI.com. Ensure you have appropriate data processing agreements in place and have informed users according to your privacy policy and local regulations (GDPR, FERPA, etc.). See <a href="{$a}">PRIVACY.md</a> for complete details.';
$string['privacy:warning:setupwizard'] = '<div class="alert alert-warning"><strong>⚠️ Privacy & Compliance Notice:</strong><br>When enabled, this plugin will transmit course content, student data, grades, and other information to ChronifyAI\'s external servers for archiving purposes.<br><br><strong>Before enabling, ensure you have:</strong><ul><li>✅ Read the <a href="../local/chronifyai/PRIVACY.md" target="_blank">Privacy Documentation</a></li><li>✅ Executed a Data Processing Agreement with ChronifyAI</li><li>✅ Updated your institutional privacy policy</li><li>✅ Informed users about external data archiving</li><li>✅ Verified compliance with applicable regulations (GDPR, FERPA, etc.)</li></ul></div>';
$string['privacy:acknowledge:label'] = 'Data Transmission Acknowledgment';
$string['privacy:acknowledge:description'] = 'I acknowledge that enabling this plugin will transmit course data, student information, grades, and other data to ChronifyAI\'s external servers. I have reviewed applicable data protection regulations and have appropriate agreements in place.';
$string['privacy:acknowledge:required'] = 'You must acknowledge the data transmission terms before enabling the plugin.';
$string['privacy:datasent:log'] = 'Data transmission to ChronifyAI: {$a}';
$string['warning:externaldatatransmission'] = '⚠️ <strong>Data Privacy Notice:</strong> ChronifyAI transmits course data including student information to external servers. Ensure you have appropriate data processing agreements in place and have informed users according to your privacy policy and local regulations (GDPR, FERPA, etc.). <a href="{$a}" target="_blank">Read Privacy Documentation</a>';
$string['privacy:setup:warning'] = '⚠️ <strong>Privacy Notice:</strong> When enabled, this plugin will transmit course content, student data, grades, and other information to ChronifyAI\'s external servers.';
$string['privacy:setup:requirements'] = 'Before enabling, ensure you have:<ul><li>Reviewed the privacy documentation (PRIVACY.md)</li><li>Consulted with your legal/compliance team</li><li>Executed data processing agreements with ChronifyAI</li><li>Updated your institutional privacy policy</li><li>Documented legal basis for data processing</li></ul>';
$string['acknowledge_data_transmission'] = 'I acknowledge external data transmission';
$string['acknowledge_data_transmission_desc'] = 'I understand that enabling this plugin will transmit course data, student information, grades, and other data to ChronifyAI\'s external servers. I have reviewed applicable data protection regulations and have appropriate agreements in place.';
$string['error:must_acknowledge'] = 'You must acknowledge the data transmission requirements before enabling the plugin.';

// Wizard interface strings (Issue #6).
$string['wizard:common:title'] = 'ChronifyAI Setup Wizard';
$string['wizard:step1:title'] = 'Welcome to ChronifyAI';
$string['wizard:step1:description'] = 'This wizard will guide you through the setup process for connecting your Moodle site to ChronifyAI. You will need your API credentials from the ChronifyAI dashboard.';
$string['wizard:step4:title'] = 'Setup Complete!';
$string['wizard:dashboard:url'] = 'https://app.chronifyai.com/dashboard';

// Settings page strings (Issue #6).
$string['settings:api:apibaseurl'] = 'API Base URL';
$string['settings:api:apibaseurl_desc'] = 'The base URL for the ChronifyAI API endpoint. Default: https://api.chronifyai.com';
$string['settings:authentication:clientid'] = 'Client ID';
$string['settings:authentication:clientid_desc'] = 'Your ChronifyAI application client ID. You can find this in your ChronifyAI dashboard.';
$string['settings:authentication:clientsecret'] = 'Client Secret';
$string['settings:authentication:clientsecret_desc'] = 'Your ChronifyAI application client secret. Keep this secure and never share it publicly.';
$string['settings:features:enabled'] = 'Enable ChronifyAI';
$string['settings:features:enabled_desc'] = 'Enable or disable ChronifyAI functionality site-wide. When disabled, no data will be transmitted to ChronifyAI servers.';

// Privacy and compliance strings (Issue #6).
$string['acknowledge_data_transmission'] = 'I acknowledge data will be transmitted to external servers';
$string['acknowledge_data_transmission_desc'] = 'By enabling this plugin, you acknowledge that student data, course content, and user information will be transmitted to ChronifyAI servers for processing. Ensure you have obtained appropriate consent from users, data processing agreements in place, reviewed PRIVACY.md documentation, and compliance with GDPR, FERPA, and local regulations.';
$string['privacy:setup:requirements'] = 'Privacy & Compliance Requirements';
$string['privacy:setup:warning'] = 'Before enabling this plugin, ensure you have reviewed all privacy implications and obtained necessary consents from users. See PRIVACY.md for complete details.';

// UI component strings (Issue #6).
$string['ui:button:save'] = 'Save';
$string['ui:button:saveandnext'] = 'Save and Continue';
$string['ui:button:saveandcomplete'] = 'Save and Complete Setup';

// Error messages (Issue #6).
$string['error:invalidurl'] = 'Invalid URL format. Please enter a valid HTTP or HTTPS URL.';
$string['error:must_acknowledge'] = 'You must acknowledge the data transmission terms before enabling the plugin.';

// Status messages (Issue #6).
$string['status:settings:saved'] = 'Settings saved successfully';
$string['status:course:found'] = 'Course found';
$string['status:course:notfound'] = 'Course not found';

// Instructor-related strings (Issue #4).
$string['instructor:unknown'] = 'Unknown';

// Task: Restore course strings (Issue #4).
$string['task:restore:lockobtained'] = 'Obtained lock for restore operation';
$string['task:restore:tempdircreated'] = 'Created temporary directory for backup file';
$string['task:restore:downloading'] = 'Downloading backup file from ChronifyAI...';
$string['task:restore:downloaded'] = 'Backup file downloaded successfully';
$string['task:restore:filesize'] = 'File size: {$a}';
$string['task:restore:preparingoptions'] = 'Preparing and validating restore options...';
$string['task:restore:initializing'] = 'Initializing restore process...';
$string['task:restore:backupcleanup'] = 'Temporary backup file cleaned up';
$string['task:restore:errorcleanup'] = 'Temporary file cleaned up after error';
$string['task:restore:lockreleased'] = 'Course restore lock released';

// Task: Backup and upload strings (Issue #4).
$string['task:backup:tempcleanup'] = 'Temporary backup file cleaned up';
$string['task:backup:storedcleanup'] = 'Stored backup file cleaned up';
$string['task:backup:errorcleanup'] = 'Temporary file cleaned up after error';

// Service: Course restore strings (Issue #4).
$string['service:restore:nameswarning'] = 'Warning: Could not update course names - keeping temporary names';
$string['service:restore:warningsignored'] = 'Restore warnings (ignored): {$a}';
$string['service:restore:continuingdespitewarnings'] = 'Continuing restore despite warnings...';
$string['service:restore:errorsmessage'] = 'Errors: {$a}';

// Task: Generate and upload report strings (Issue #4).
$string['task:report:unexpectedformat'] = 'Unexpected response format';
$string['task:report:unexpectederror'] = 'Unexpected error during report upload: {$a}';

// Restore task error messages (for field validation).
$string['error:restore:missingcourseid'] = 'Course ID is required for restore operation';
$string['error:restore:missingbackupid'] = 'Backup ID is required for restore operation';
$string['error:restore:missingexternaluserid'] = 'External user ID is required for restore operation';
