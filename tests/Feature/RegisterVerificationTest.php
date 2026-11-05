<?php

use App\Livewire\Storefront\Auth\Register;
use App\Models\User;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config(['services.kasabazaar.base_url' => 'https://kasabazaar.test/api/v1']);
});

function signedUpUser(): array
{
    return ['success' => true, 'data' => [
        'token' => 'token-123',
        'user' => ['id' => 'u-1', 'name' => 'Ama Mensah', 'email' => 'ama@example.com'],
    ]];
}

function fillRegister($component)
{
    return $component
        ->set('name', 'Ama Mensah')
        ->set('email', 'ama@example.com')
        ->set('phone', '0241234567')
        ->set('password', 'secret-pass')
        ->set('password_confirmation', 'secret-pass');
}

it('signs a new shopper straight in', function () {
    Http::fake(['kasabazaar.test/api/v1/customer/auth/register' => Http::response(signedUpUser(), 201)]);

    fillRegister(Livewire::test(Register::class))
        ->call('submit')
        ->assertRedirect(route('storefront.account.dashboard'));

    expect(User::where('kasabazaar_user_id', 'u-1')->exists())->toBeTrue();
});

it('asks an existing shipping customer for the code before signing in', function () {
    Http::fake([
        'kasabazaar.test/api/v1/customer/auth/register' => Http::response(['success' => true, 'data' => [
            'requires_verification' => true,
            'verification_id' => 'v-1',
            'channel' => 'sms',
            'masked_destination' => '••••••4567',
        ]], 202),
        'kasabazaar.test/api/v1/customer/auth/verify-otp' => Http::response(signedUpUser(), 201),
    ]);

    $component = fillRegister(Livewire::test(Register::class))
        ->call('submit')
        ->assertSet('verificationId', 'v-1')
        ->assertSee('••••••4567')
        ->assertNoRedirect();

    expect(User::count())->toBe(0);

    $component->set('code', '123456')
        ->call('verify')
        ->assertRedirect(route('storefront.account.dashboard'));

    Http::assertSent(fn (Request $request) => str_ends_with($request->url(), 'verify-otp')
        && $request['verification_id'] === 'v-1'
        && $request['code'] === '123456');
});

it('shows the reason when the code is wrong', function () {
    Http::fake([
        'kasabazaar.test/api/v1/customer/auth/register' => Http::response(['success' => true, 'data' => [
            'requires_verification' => true, 'verification_id' => 'v-1', 'channel' => 'sms', 'masked_destination' => '••••4567',
        ]], 202),
        'kasabazaar.test/api/v1/customer/auth/verify-otp' => Http::response(['success' => false, 'message' => 'That code is not right. Check it and try again.'], 422),
    ]);

    fillRegister(Livewire::test(Register::class))
        ->call('submit')
        ->set('code', '000000')
        ->call('verify')
        ->assertSet('error', 'That code is not right. Check it and try again.')
        ->assertNoRedirect();
});
