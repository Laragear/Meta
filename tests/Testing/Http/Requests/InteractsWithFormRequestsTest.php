<?php

namespace Tests\Testing\Http\Requests;

use Illuminate\Auth\GenericUser;
use Illuminate\Foundation\Http\FormRequest;
use Laragear\Meta\Testing\Http\Requests\InteractsWithFormRequests;
use Laragear\Meta\Testing\Http\Requests\PendingTestFormRequest;
use Orchestra\Testbench\TestCase;
use PHPUnit\Framework\AssertionFailedError;

class InteractsWithFormRequestsTest extends TestCase
{
    use InteractsWithFormRequests;

    protected PendingTestFormRequest $formRequest;

    protected function setUp(): void
    {
        parent::setUp();

        $this->formRequest = $this->formRequest(TestFormRequest::class, ['foo' => 'bar']);
    }

    public function test_creates_form_request(): void
    {
        static::assertInstanceOf(TestFormRequest::class, $this->formRequest->getFormRequest());
        static::assertSame(['foo' => 'bar'], $this->formRequest->getFormRequest()->all());
    }

    public function test_acting_as(): void
    {
        static::assertNull($this->formRequest->getFormRequest()->user());

        $this->formRequest->actingAs($user = new GenericUser(['baz' => 'quz']));

        static::assertSame($user, $this->formRequest->getFormRequest()->user());
    }

    public function test_be(): void
    {
        static::assertNull($this->formRequest->getFormRequest()->user());

        $this->formRequest->be($user = new GenericUser(['baz' => 'quz']));

        static::assertSame($user, $this->formRequest->getFormRequest()->user());
    }

    public function test_assert_allowed(): void
    {
        $this->formRequest->assertAllowed();
    }

    public function test_assert_allowed_fails(): void
    {
        $this->formRequest->getFormRequest()->request->set('deny', true);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The Form Request 'Tests\Http\Requests\TestFormRequest' fails authorization.");

        $this->formRequest->assertAllowed();
    }

    public function test_assert_denied(): void
    {
        $this->formRequest->getFormRequest()->request->set('deny', true);

        $this->formRequest->assertDenied();
    }

    public function test_assert_denied_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The Form Request 'Tests\Http\Requests\TestFormRequest' passes authorization.");

        $this->formRequest->assertDenied();
    }

    public function test_assert_validation_passes(): void
    {
        $this->formRequest->assertValidationPasses();
    }

    public function test_assert_validation_passes_fails(): void
    {
        $this->formRequest->getFormRequest()->request->set('foo', 'not_bar');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The Form Request 'Tests\Http\Requests\TestFormRequest' fails validation.");

        $this->formRequest->assertValidationPasses();
    }

    public function test_assert_validation_fails(): void
    {
        $this->formRequest->getFormRequest()->request->set('foo', 'not_bar');

        $this->formRequest->assertValidationFails();
    }

    public function test_assert_validation_fails_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The Form Request 'Tests\Http\Requests\TestFormRequest' passes validation.");

        $this->formRequest->assertValidationFails();
    }

    public function test_assert_ok(): void
    {
        $this->formRequest->assertOk();
    }

    public function test_assert_ok_fails_for_authorization(): void
    {
        $this->formRequest->getFormRequest()->request->set('deny', true);

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The Form Request 'Tests\Http\Requests\TestFormRequest' fails authorization.");

        $this->formRequest->assertOk();
    }

    public function test_assert_ok_fails_for_validation(): void
    {
        $this->formRequest->getFormRequest()->request->set('foo', 'not_bar');

        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The Form Request 'Tests\Http\Requests\TestFormRequest' fails validation.");

        $this->formRequest->assertOk();
    }

    public function test_assert_form_data(): void
    {
        $this->formRequest->assertFormData(['baz' => 'quz']);
    }

    public function test_assert_form_data_fails(): void
    {
        $this->expectException(AssertionFailedError::class);
        $this->expectExceptionMessage("The form 'foo' is not equal to the value issued.");

        $this->formRequest->assertFormData(['foo' => 'quz']);
    }
}

class TestFormRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! $this->has('deny');
    }

    public function rules(): array
    {
        return [
            'foo' => 'required|in:bar',
        ];
    }

    public function passedValidation(): void
    {
        $this->request->set('baz', 'quz');
    }
}
