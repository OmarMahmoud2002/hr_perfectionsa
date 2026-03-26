@extends('layouts.app')

@section('title', 'تغيير كلمة المرور')
@section('page-title', 'تغيير كلمة المرور')
@section('page-subtitle', 'مطلوب قبل متابعة استخدام النظام')

@section('content')
<div class="max-w-xl mx-auto">
    <div class="card overflow-hidden">
        <div class="px-6 py-5 border-b border-slate-100"
             style="background: linear-gradient(135deg, rgba(69,150,207,0.06), rgba(231,197,57,0.08));">
            <h2 class="font-bold text-slate-800">مرحباً {{ $user->name }}</h2>
            <p class="text-sm text-slate-500 mt-1">لازم تغيّر كلمة المرور الأولية قبل الدخول لباقي الصفحات.</p>
        </div>

          <form method="POST" action="{{ route('password.force-change.update') }}" class="p-6 space-y-5"
              data-loading="true" data-loading-text="جاري التحديث...">
            @csrf
            @method('PUT')

            <div class="form-group">
                <label for="password" class="form-label">كلمة المرور الجديدة</label>
                <input id="password" type="password" name="password" class="form-input @error('password') border-red-400 @enderror" required>
                @error('password')
                    <p class="form-error">{{ $message }}</p>
                @enderror
            </div>

            <div class="form-group">
                <label for="password_confirmation" class="form-label">تأكيد كلمة المرور</label>
                <input id="password_confirmation" type="password" name="password_confirmation" class="form-input" required>
            </div>

            <div class="pt-2 border-t border-slate-100">
                <button type="submit" class="btn-primary btn-lg w-full justify-center">حفظ كلمة المرور</button>
            </div>
        </form>

        <div class="px-6 pb-6">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="btn-ghost btn-lg w-full">تسجيل خروج</button>
            </form>
        </div>
    </div>
</div>
@endsection
