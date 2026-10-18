<?php

use App\Models\Tuk;
use App\Models\User;

test('asesor can view tuk but can not change it', function () {
    $asesor = User::factory()->asesor()->create();
    $tuk = Tuk::factory()->create();

    $this->actingAs($asesor)->get('/master/tuk')->assertOk();
    $this->actingAs($asesor)->get("/master/tuk/{$tuk->id_tuk}/edit")->assertForbidden();
    $this->actingAs($asesor)->delete("/master/tuk/{$tuk->id_tuk}")->assertForbidden();
});

test('deleting a tuk is a soft delete', function () {
    $tuk = Tuk::factory()->create();

    $this->actingAs(User::factory()->admin()->create())->delete("/master/tuk/{$tuk->id_tuk}")->assertSessionHas('success');

    $this->assertSoftDeleted($tuk);
});
