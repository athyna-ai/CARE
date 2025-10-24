<?php
// =============================================================================
// ABOUT US PAGE - CONTENT EDITING GUIDE
// =============================================================================
// 
// ⚠️  DO NOT EDIT THE PHP CODE BELOW (Lines 1-16) ⚠️
// This section handles security, authentication, and page setup
// 
// ✅ SAFE TO EDIT: HTML CONTENT SECTION (Lines 18-45)
// You can safely edit the content between the <div> tags
// 
// ⚠️  DO NOT EDIT: FOOTER INCLUDE (Line 47) ⚠️
// This includes the page footer
//
// =============================================================================

declare(strict_types=1);
require_once __DIR__ . '/../core/config.php';
require_once __DIR__ . '/../core/helpers.php';

// Include security breach detection
require_once __DIR__ . '/../security_breach_detector.php';

// REQUIRE ADMIN AUTHENTICATION
require_admin_auth();

$pageTitle = 'About Us';
$showTopNav = true;
$showSidebar = true;
include __DIR__ . '/../partials/header.php';
?>

<!-- ============================================================================= -->
<!-- ✅ SAFE TO EDIT: HTML CONTENT SECTION STARTS HERE -->
<!-- You can edit everything between this comment and the "ENDS HERE" comment below -->
<!-- ============================================================================= -->

<div class="min-h-screen bg-gradient-to-br from-clinic-ivory/30 via-white to-clinic-tea/20 pt-24 pb-12 px-4">
    <div class="max-w-6xl mx-auto">
        <!-- Page Header -->
        <div class="text-center mb-12">
            <div class="inline-flex items-center justify-center w-20 h-20 bg-gradient-to-br from-clinic-blue to-clinic-tea rounded-3xl mb-6 shadow-xl">
                <svg class="w-10 h-10 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <h1 class="text-4xl md:text-5xl font-comfortaa font-bold text-clinic-dark mb-4">CARE System Guide</h1>
            <p class="text-lg text-clinic-dark/70 max-w-3xl mx-auto">
                A comprehensive guide to using the Clinic Administration of Records System
            </p>
            <div class="w-24 h-1 bg-gradient-to-r from-clinic-blue to-clinic-tea mx-auto rounded-full mt-6"></div>
        </div>

        <!-- Category Navigation Pills -->
        <div class="bg-white/80 backdrop-blur rounded-2xl border border-clinic-tea/20 shadow-xl p-4 mb-8">
            <div class="flex flex-wrap gap-2 justify-center">
                <button onclick="showCategory('dashboard')" class="category-btn active px-4 py-2 rounded-xl font-medium text-sm transition-all duration-300" data-category="dashboard">
                    Dashboard
                </button>
                <button onclick="showCategory('registration')" class="category-btn px-4 py-2 rounded-xl font-medium text-sm transition-all duration-300" data-category="registration">
                    Registration
                </button>
                <button onclick="showCategory('patient-management')" class="category-btn px-4 py-2 rounded-xl font-medium text-sm transition-all duration-300" data-category="patient-management">
                    Patient Management
                </button>
                <button onclick="showCategory('medical-records')" class="category-btn px-4 py-2 rounded-xl font-medium text-sm transition-all duration-300" data-category="medical-records">
                    Medical Records
                </button>
                <button onclick="showCategory('visitation')" class="category-btn px-4 py-2 rounded-xl font-medium text-sm transition-all duration-300" data-category="visitation">
                    Visitation Logs
                </button>
                <button onclick="showCategory('archive')" class="category-btn px-4 py-2 rounded-xl font-medium text-sm transition-all duration-300" data-category="archive">
                    Archive
                </button>
                <button onclick="showCategory('reports')" class="category-btn px-4 py-2 rounded-xl font-medium text-sm transition-all duration-300" data-category="reports">
                    Reports
                </button>
                <button onclick="showCategory('settings')" class="category-btn px-4 py-2 rounded-xl font-medium text-sm transition-all duration-300" data-category="settings">
                    Settings
                </button>
            </div>
        </div>

        <!-- Category Content -->
        <div class="space-y-8">
            
            <!-- Dashboard Category -->
            <div id="category-dashboard" class="category-content">
                <div class="bg-white/90 backdrop-blur rounded-2xl border border-clinic-blue/20 shadow-xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-clinic-blue/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 7v10a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2H5a2 2 0 00-2-2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-clinic-dark">Dashboard Overview</h2>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-l-4 border-clinic-blue pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">What is it?</h3>
                            <p class="text-clinic-dark/70">The Dashboard is your central hub for monitoring all clinic activities, statistics, and quick access to essential functions.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-tea pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Why use it?</h3>
                            <ul class="list-disc list-inside text-clinic-dark/70 space-y-2">
                                <li>Get a quick overview of daily clinic activities</li>
                                <li>Monitor patient statistics and trends</li>
                                <li>Access important notifications and alerts</li>
                                <li>View recent visitations and medical records</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-vanilla pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Where to find it?</h3>
                            <p class="text-clinic-dark/70">Click the <span class="font-semibold text-clinic-blue">Dashboard</span> link in the sidebar navigation menu.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-green pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">How to use it?</h3>
                            <ol class="list-decimal list-inside text-clinic-dark/70 space-y-2">
                                <li>Review the statistics cards at the top for total counts</li>
                                <li>Check recent activities in the activity feed</li>
                                <li>Click on any card to navigate to detailed views</li>
                                <li>Monitor notifications bell icon for important alerts</li>
                            </ol>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registration Category -->
            <div id="category-registration" class="category-content hidden">
                <div class="bg-white/90 backdrop-blur rounded-2xl border border-clinic-tea/20 shadow-xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-clinic-tea/20 flex items-center justify-center">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-clinic-dark">Patient Registration</h2>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-l-4 border-clinic-blue pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">What is it?</h3>
                            <p class="text-clinic-dark/70">The Registration module allows you to register new students and faculty members into the clinic system, including their personal information, emergency contacts, and RFID assignment.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-tea pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Why use it?</h3>
                            <ul class="list-disc list-inside text-clinic-dark/70 space-y-2">
                                <li>Create patient profiles for all school members</li>
                                <li>Store essential contact and emergency information</li>
                                <li>Enable quick patient lookup via RFID scanning</li>
                                <li>Maintain organized patient database</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-vanilla pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Where to find it?</h3>
                            <p class="text-clinic-dark/70">In the sidebar under <span class="font-semibold text-clinic-blue">REGISTRATION</span> section:</p>
                            <ul class="list-disc list-inside ml-4 mt-2 text-clinic-dark/70">
                                <li><span class="font-semibold">Register Student</span> - For student registration</li>
                                <li><span class="font-semibold">Register Faculty</span> - For faculty/staff registration</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-green pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">How to use it?</h3>
                            <div class="text-clinic-dark/70 space-y-3">
                                <p class="font-semibold text-clinic-blue">For Student Registration:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Fill in student personal information (name, age, gender, etc.)</li>
                                    <li>Enter school information (ID, grade level, section)</li>
                                    <li>Add parent/guardian contact details</li>
                                    <li>Assign RFID card (optional)</li>
                                    <li>Click "Register Student" to save</li>
                                </ol>
                                
                                <p class="font-semibold text-clinic-blue mt-4">For Faculty Registration:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Enter faculty personal information</li>
                                    <li>Specify position/designation</li>
                                    <li>Add emergency contact information</li>
                                    <li>Assign RFID card (optional)</li>
                                    <li>Click "Register Faculty" to save</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Patient Management Category -->
            <div id="category-patient-management" class="category-content hidden">
                <div class="bg-white/90 backdrop-blur rounded-2xl border border-clinic-purple/20 shadow-xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-clinic-purple/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-clinic-dark">Patient Management & Search</h2>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-l-4 border-clinic-blue pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">What is it?</h3>
                            <p class="text-clinic-dark/70">A powerful search and management system to find, view, update, and manage patient records for both students and faculty members.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-tea pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Why use it?</h3>
                            <ul class="list-disc list-inside text-clinic-dark/70 space-y-2">
                                <li>Quickly find patient records using RFID or manual search</li>
                                <li>View complete patient profiles and medical history</li>
                                <li>Update patient information as needed</li>
                                <li>Manage enrollment status and re-enrollment</li>
                                <li>Archive inactive patients</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-vanilla pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Where to find it?</h3>
                            <p class="text-clinic-dark/70">Click <span class="font-semibold text-clinic-blue">Search</span> in the sidebar under PATIENTS section to access the RFID Portal for patient lookup.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-green pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">How to use it?</h3>
                            <div class="text-clinic-dark/70 space-y-3">
                                <p class="font-semibold text-clinic-blue">Search Methods:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li><span class="font-semibold">RFID Scan:</span> Simply scan the patient's RFID card</li>
                                    <li><span class="font-semibold">Manual Search:</span> Enter name, ID, or other details in the search box</li>
                                    <li><span class="font-semibold">Browse Lists:</span> View all students or faculty in organized tables</li>
                                </ol>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Available Actions:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li><span class="font-semibold">View:</span> Click on a patient to see their complete profile</li>
                                    <li><span class="font-semibold">Edit:</span> Update patient information and contact details</li>
                                    <li><span class="font-semibold">Medical Records:</span> Add or view medical history and forms</li>
                                    <li><span class="font-semibold">Re-enroll:</span> Process student re-enrollment for new school year</li>
                                    <li><span class="font-semibold">Archive:</span> Move inactive patients to archive</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Medical Records Category -->
            <div id="category-medical-records" class="category-content hidden">
                <div class="bg-white/90 backdrop-blur rounded-2xl border border-clinic-green/20 shadow-xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-clinic-green/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-clinic-dark">Medical Records & Forms</h2>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-l-4 border-clinic-blue pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">What is it?</h3>
                            <p class="text-clinic-dark/70">A comprehensive medical records system to document patient medical history, allergies, conditions, medications, and clinic visitations with detailed medical forms.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-tea pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Why use it?</h3>
                            <ul class="list-disc list-inside text-clinic-dark/70 space-y-2">
                                <li>Maintain complete medical history for each patient</li>
                                <li>Track allergies and medical conditions</li>
                                <li>Document clinic visits with detailed forms</li>
                                <li>Record medications and treatments given</li>
                                <li>Generate medical certificates and reports</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-vanilla pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Where to find it?</h3>
                            <p class="text-clinic-dark/70">Access medical records through:</p>
                            <ul class="list-disc list-inside ml-4 mt-2 text-clinic-dark/70">
                                <li>Patient profile page - "Medical Records" tab</li>
                                <li>Patient view page - "View Medical History" button</li>
                                <li>During RFID scan - automatically shows medical info</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-green pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">How to use it?</h3>
                            <div class="text-clinic-dark/70 space-y-3">
                                <p class="font-semibold text-clinic-blue">Adding Medical History:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Navigate to patient profile</li>
                                    <li>Click "Add Medical History" button</li>
                                    <li>Fill in allergies, conditions, medications</li>
                                    <li>Add emergency medical information</li>
                                    <li>Save the medical history</li>
                                </ol>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Recording Clinic Visits:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Scan patient RFID or search for patient</li>
                                    <li>Click "New Visit" or "Add Visitation"</li>
                                    <li>Record chief complaint and symptoms</li>
                                    <li>Document vital signs (temperature, BP, etc.)</li>
                                    <li>Record diagnosis and treatment given</li>
                                    <li>Add medications dispensed</li>
                                    <li>Save the visitation record</li>
                                </ol>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Managing Forms:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>View all medical forms for a patient</li>
                                    <li>Edit existing medical records if needed</li>
                                    <li>Delete outdated or incorrect entries</li>
                                    <li>Export medical records for reporting</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Visitation Logs Category -->
            <div id="category-visitation" class="category-content hidden">
                <div class="bg-white/90 backdrop-blur rounded-2xl border border-clinic-orange/20 shadow-xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-clinic-orange/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-clinic-dark">Visitation Logs & Tracking</h2>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-l-4 border-clinic-blue pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">What is it?</h3>
                            <p class="text-clinic-dark/70">A comprehensive logging system that tracks all patient visits to the clinic, including check-in/check-out times, complaints, treatments, and outcomes.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-tea pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Why use it?</h3>
                            <ul class="list-disc list-inside text-clinic-dark/70 space-y-2">
                                <li>Track all clinic visits chronologically</li>
                                <li>Monitor patient traffic and peak hours</li>
                                <li>Review treatment history and outcomes</li>
                                <li>Generate visit reports for specific periods</li>
                                <li>Ensure proper documentation for each visit</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-vanilla pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Where to find it?</h3>
                            <p class="text-clinic-dark/70">Click <span class="font-semibold text-clinic-blue">Visitation Logs</span> in the sidebar under MANAGEMENT section.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-green pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">How to use it?</h3>
                            <div class="text-clinic-dark/70 space-y-3">
                                <p class="font-semibold text-clinic-blue">Viewing Logs:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Access Visitation Logs from sidebar</li>
                                    <li>View table of all recent visits</li>
                                    <li>Use date filters to find specific periods</li>
                                    <li>Search for specific patients or conditions</li>
                                    <li>Click on any entry to see full details</li>
                                </ol>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Available Information:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>Patient name and ID</li>
                                    <li>Visit date and time</li>
                                    <li>Chief complaint/reason for visit</li>
                                    <li>Vital signs recorded</li>
                                    <li>Diagnosis and treatment given</li>
                                    <li>Medications dispensed</li>
                                    <li>Status (admitted, discharged, referred)</li>
                                </ul>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Actions:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>View detailed visit information</li>
                                    <li>Export logs for reporting</li>
                                    <li>Archive old visitation records</li>
                                    <li>Restore archived visits if needed</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Archive Category -->
            <div id="category-archive" class="category-content hidden">
                <div class="bg-white/90 backdrop-blur rounded-2xl border border-clinic-dark/20 shadow-xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-clinic-dark/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-clinic-dark">Archive Management</h2>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-l-4 border-clinic-blue pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">What is it?</h3>
                            <p class="text-clinic-dark/70">A secure storage system for inactive patient records, old medical records, and historical visitation logs that are no longer actively needed but must be retained.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-tea pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Why use it?</h3>
                            <ul class="list-disc list-inside text-clinic-dark/70 space-y-2">
                                <li>Keep active database clean and fast</li>
                                <li>Maintain historical records for compliance</li>
                                <li>Organize graduated students and former faculty</li>
                                <li>Preserve old medical records</li>
                                <li>Restore records if needed</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-vanilla pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Where to find it?</h3>
                            <p class="text-clinic-dark/70">Click <span class="font-semibold text-clinic-blue">Archive</span> in the sidebar under MANAGEMENT section.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-green pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">How to use it?</h3>
                            <div class="text-clinic-dark/70 space-y-3">
                                <p class="font-semibold text-clinic-blue">Archiving Records:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Navigate to patient or record to archive</li>
                                    <li>Click "Archive" button</li>
                                    <li>Confirm archival action</li>
                                    <li>Record is moved to archive storage</li>
                                </ol>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Viewing Archives:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Go to Archive Management page</li>
                                    <li>Choose category: Students, Faculty, Medical Records, or Visitations</li>
                                    <li>Browse archived records</li>
                                    <li>Search for specific archived items</li>
                                    <li>Click to view details</li>
                                </ol>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Restoring Records:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Find the archived record</li>
                                    <li>Click "Restore" button</li>
                                    <li>Confirm restoration</li>
                                    <li>Record returns to active database</li>
                                </ol>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Reports & Analytics Category -->
            <div id="category-reports" class="category-content hidden">
                <div class="bg-white/90 backdrop-blur rounded-2xl border border-clinic-purple/20 shadow-xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-clinic-purple/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"></path>
                            </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-clinic-dark">Reports & Analytics</h2>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-l-4 border-clinic-blue pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">What is it?</h3>
                            <p class="text-clinic-dark/70">A powerful analytics dashboard that provides insights into clinic operations, patient statistics, disease trends, and generates various reports for administration.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-tea pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Why use it?</h3>
                            <ul class="list-disc list-inside text-clinic-dark/70 space-y-2">
                                <li>Track clinic performance metrics</li>
                                <li>Identify health trends and patterns</li>
                                <li>Generate reports for school administration</li>
                                <li>Monitor common illnesses and outbreaks</li>
                                <li>Make data-driven decisions</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-vanilla pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Where to find it?</h3>
                            <p class="text-clinic-dark/70">Click <span class="font-semibold text-clinic-blue">Reports & Analytics</span> in the sidebar under MANAGEMENT section.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-green pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">How to use it?</h3>
                            <div class="text-clinic-dark/70 space-y-3">
                                <p class="font-semibold text-clinic-blue">Available Reports:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li><span class="font-semibold">Daily Visit Reports:</span> Track daily clinic traffic</li>
                                    <li><span class="font-semibold">Monthly Statistics:</span> View monthly health trends</li>
                                    <li><span class="font-semibold">Disease Reports:</span> Track common illnesses</li>
                                    <li><span class="font-semibold">Patient Demographics:</span> Analyze patient distribution</li>
                                    <li><span class="font-semibold">Medication Usage:</span> Monitor medicine inventory</li>
                                </ul>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Generating Reports:</p>
                                <ol class="list-decimal list-inside space-y-2 ml-4">
                                    <li>Select report type from dashboard</li>
                                    <li>Choose date range or filter criteria</li>
                                    <li>Click "Generate Report"</li>
                                    <li>View results in charts and tables</li>
                                    <li>Export to PDF or Excel if needed</li>
                                </ol>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Analytics Features:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>Interactive charts and graphs</li>
                                    <li>Trend analysis over time</li>
                                    <li>Comparison reports</li>
                                    <li>Customizable filters</li>
                                    <li>Data export capabilities</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Settings Category -->
            <div id="category-settings" class="category-content hidden">
                <div class="bg-white/90 backdrop-blur rounded-2xl border border-clinic-blue/20 shadow-xl p-8">
                    <div class="flex items-center gap-4 mb-6">
                        <div class="w-12 h-12 rounded-xl bg-clinic-blue/10 flex items-center justify-center">
                            <svg class="w-6 h-6 text-clinic-blue" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"></path>
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                </svg>
                        </div>
                        <h2 class="text-3xl font-bold text-clinic-dark">System Settings & Administration</h2>
                    </div>
                    
                    <div class="space-y-6">
                        <div class="border-l-4 border-clinic-blue pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">What is it?</h3>
                            <p class="text-clinic-dark/70">Comprehensive system configuration and administration tools to manage users, security settings, database backups, and system preferences.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-tea pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Why use it?</h3>
                            <ul class="list-disc list-inside text-clinic-dark/70 space-y-2">
                                <li>Manage administrator accounts</li>
                                <li>Configure security settings</li>
                                <li>Backup and restore database</li>
                                <li>Monitor system security and logs</li>
                                <li>Customize system preferences</li>
                            </ul>
                        </div>
                        
                        <div class="border-l-4 border-clinic-vanilla pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">Where to find it?</h3>
                            <p class="text-clinic-dark/70">Click <span class="font-semibold text-clinic-blue">Settings</span> in the sidebar under MANAGEMENT section.</p>
                        </div>
                        
                        <div class="border-l-4 border-clinic-green pl-6">
                            <h3 class="text-xl font-semibold text-clinic-dark mb-2">How to use it?</h3>
                            <div class="text-clinic-dark/70 space-y-3">
                                <p class="font-semibold text-clinic-blue">Account Management:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>Create new administrator accounts</li>
                                    <li>Update profile information</li>
                                    <li>Change passwords</li>
                                    <li>Manage RFID access</li>
                                    <li>View login history</li>
                                </ul>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Security Settings:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>Configure password policies</li>
                                    <li>Set session timeout periods</li>
                                    <li>Enable two-factor authentication</li>
                                    <li>Monitor security alerts</li>
                                    <li>Review activity logs</li>
                                    <li>Manage IP blocks and rate limiting</li>
                                </ul>
                                
                                <p class="font-semibold text-clinic-blue mt-4">Database Management:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>Create database backups</li>
                                    <li>Restore from backups</li>
                                    <li>Schedule automatic backups</li>
                                    <li>Clean up old logs</li>
                                    <li>Optimize database performance</li>
                                </ul>
                                
                                <p class="font-semibold text-clinic-blue mt-4">System Preferences:</p>
                                <ul class="list-disc list-inside space-y-2 ml-4">
                                    <li>Set timezone and date formats</li>
                                    <li>Configure notification settings</li>
                                    <li>Customize system appearance</li>
                                    <li>Manage system permissions</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Back to Dashboard Button -->
        <div class="text-center mt-12">
            <a href="../admin/dashboard.php" class="inline-flex items-center gap-3 px-8 py-4 bg-clinic-blue text-white rounded-2xl hover:bg-clinic-blue/90 transition-all duration-300 shadow-lg hover:shadow-xl transform hover:-translate-y-1">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path>
                </svg>
                Back to Dashboard
            </a>
        </div>
    </div>

    <!-- Development Team Section -->
    <div class="py-20 bg-gradient-to-br from-clinic-tea/5 to-clinic-blue/5 mt-20">
        <div class="max-w-6xl mx-auto px-4">
            <div class="text-center mb-16">
                <h2 class="text-4xl md:text-5xl font-comfortaa font-bold text-clinic-dark mb-6">
                    Meet Our Development Team
                </h2>
                <div class="w-24 h-1 bg-gradient-to-r from-clinic-blue to-clinic-tea mx-auto rounded-full"></div>
                <p class="text-lg text-clinic-dark/70 mt-6 max-w-3xl mx-auto">
                    The talented developers behind the CARE system, dedicated to creating innovative solutions for our school's healthcare management needs.
                </p>
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                <!-- Developer 1 -->
                <div class="developer-card bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300 text-center transform hover:-translate-y-2">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-clinic-blue to-clinic-tea flex items-center justify-center">
                        <span class="text-white font-bold text-xl">HM</span>
                    </div>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-2">Hannah Athena A. Mauricio</h3>
                    <p class="text-clinic-dark/60 text-sm">Lead & Backend Developer</p>
                </div>

                <!-- Developer 2 -->
                <div class="developer-card bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300 text-center transform hover:-translate-y-2">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-clinic-tea to-clinic-vanilla flex items-center justify-center">
                        <span class="text-white font-bold text-xl">JL</span>
                    </div>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-2">Jeremae L. Lalo</h3>
                    <p class="text-clinic-dark/60 text-sm">Design & Frontend Developer</p>
                </div>

                <!-- Developer 3 -->
                <div class="developer-card bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300 text-center transform hover:-translate-y-2">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-clinic-vanilla to-clinic-blue flex items-center justify-center">
                        <span class="text-white font-bold text-xl">SO</span>
                    </div>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-2">Shanael Angelyn N. Orodio</h3>
                    <p class="text-clinic-dark/60 text-sm">Design & Frontend Developer</p>
                </div>

                <!-- Developer 4 -->
                <div class="developer-card bg-white/80 backdrop-blur-md rounded-2xl p-6 shadow-xl border border-clinic-tea/20 hover:shadow-2xl transition-all duration-300 text-center transform hover:-translate-y-2">
                    <div class="w-20 h-20 mx-auto mb-4 rounded-full bg-gradient-to-br from-clinic-blue to-clinic-vanilla flex items-center justify-center">
                        <span class="text-white font-bold text-xl">DB</span>
                    </div>
                    <h3 class="text-xl font-semibold text-clinic-dark mb-2">Dhennis Jhon P. Biag</h3>
                    <p class="text-clinic-dark/60 text-sm">Backend Support</p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
/* Category Button Styles */
.category-btn {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    color: #495057;
    border: 1px solid #dee2e6;
}

.category-btn:hover {
    background: linear-gradient(135deg, #e9ecef 0%, #dee2e6 100%);
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.category-btn.active {
    background: linear-gradient(135deg, #3b82f6 0%, #06b6d4 100%);
    color: white;
    border-color: #3b82f6;
    box-shadow: 0 4px 16px rgba(59, 130, 246, 0.3);
}

/* Smooth transitions */
.category-content {
    animation: fadeInUp 0.5s ease-out;
}

@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

/* Developer card hover effects */
.developer-card {
    transition: all 0.3s ease;
}

/* Scrollbar Styles */
::-webkit-scrollbar {
    width: 8px;
}

::-webkit-scrollbar-track {
    background: #f1f1f1;
}

::-webkit-scrollbar-thumb {
    background: linear-gradient(to bottom, #3b82f6, #06b6d4);
    border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
    background: linear-gradient(to bottom, #2563eb, #0891b2);
}

/* Responsive adjustments */
@media (max-width: 768px) {
    .category-btn {
        font-size: 0.75rem;
        padding: 0.5rem 0.75rem;
    }
}
</style>

<script>
function showCategory(categoryId) {
    // Hide all categories
    const allCategories = document.querySelectorAll('.category-content');
    allCategories.forEach(cat => {
        cat.classList.add('hidden');
    });
    
    // Show selected category
    const selectedCategory = document.getElementById('category-' + categoryId);
    if (selectedCategory) {
        selectedCategory.classList.remove('hidden');
    }
    
    // Update button states
    const allButtons = document.querySelectorAll('.category-btn');
    allButtons.forEach(btn => {
        btn.classList.remove('active');
    });
    
    const activeButton = document.querySelector(`[data-category="${categoryId}"]`);
    if (activeButton) {
        activeButton.classList.add('active');
    }
    
    // Scroll to top of content
    window.scrollTo({ top: 0, behavior: 'smooth' });
}

// Initialize with dashboard category visible
document.addEventListener('DOMContentLoaded', function() {
    showCategory('dashboard');
});
</script>

<!-- ============================================================================= -->
<!-- ✅ SAFE TO EDIT: HTML CONTENT SECTION ENDS HERE -->
<!-- ============================================================================= -->

<!-- ⚠️  DO NOT EDIT: FOOTER INCLUDE BELOW ⚠️ -->
<?php include __DIR__ . '/../partials/footer.php'; ?>
