<?php

use App\Models\User;
use App\Models\Patient;
use App\Models\Site;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('admin can access all PHI data', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->get('/api/patients/' . $patient->id)
        ->assertOk()
        ->assertJsonStructure([
            'id',
            'p_number',
            'date_of_birth',
            'gender'
        ]);
});

test('clinician can access PHI data for assigned facilities', function () {
    $clinician = User::factory()->create(['role' => 'clinician', 'facility_ids' => [1]]);
    $site = Site::factory()->create(['id' => 1]);
    $patient = Patient::factory()->create(['site_id' => 1]);

    $this->actingAs($clinician)
        ->get('/api/patients/' . $patient->id)
        ->assertOk();
});

test('clinician cannot access PHI data for unassigned facilities', function () {
    $clinician = User::factory()->create(['role' => 'clinician', 'facility_ids' => [1]]);
    $patient = Patient::factory()->create(['site_id' => 2]);

    $this->actingAs($clinician)
        ->get('/api/patients/' . $patient->id)
        ->assertForbidden();
});

test('researcher can only access aggregated data', function () {
    $researcher = User::factory()->create(['role' => 'researcher']);
    Patient::factory()->count(10)->create();

    $this->actingAs($researcher)
        ->get('/api/reports/aggregated')
        ->assertOk()
        ->assertJsonMissing(['p_number', 'date_of_birth']);
});

test('auditor can only access audit logs', function () {
    $auditor = User::factory()->create(['role' => 'auditor']);

    $this->actingAs($auditor)
        ->get('/api/audit-logs')
        ->assertOk();

    $this->actingAs($auditor)
        ->get('/api/patients/1')
        ->assertForbidden();
});

test('PHI access is logged in audit trail', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $patient = Patient::factory()->create();

    $this->actingAs($admin)
        ->get('/api/patients/' . $patient->id);

    // Verify audit log entry was created
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $admin->id,
        'action' => 'access',
        'resource_type' => 'patient',
        'resource_id' => $patient->id,
    ]);
});

test('PHI data is encrypted at rest', function () {
    $patient = Patient::factory()->create([
        'p_number' => '12345'
    ]);

    // Check that sensitive data is encrypted in database
    $rawPatient = DB::table('patients')->where('id', $patient->id)->first();
    expect($rawPatient->p_number)->not->toBe('12345'); // Should be encrypted
});

test('unauthorized access attempts are logged', function () {
    $researcher = User::factory()->create(['role' => 'researcher']);
    $patient = Patient::factory()->create();

    $this->actingAs($researcher)
        ->get('/api/patients/' . $patient->id)
        ->assertForbidden();

    // Verify unauthorized access attempt was logged
    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $researcher->id,
        'action' => 'access_denied',
        'resource_type' => 'patient',
        'resource_id' => $patient->id,
    ]);
});
