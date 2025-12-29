@extends('guide.layout')

@section('content')
    <div class="mb-12">
        <h1 class="text-3xl font-extrabold text-slate-900">Frequently Asked Questions</h1>
        <p class="mt-4 text-lg text-slate-600">Common questions and troubleshooting tips.</p>
    </div>

    <!-- General Questions -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">General</h2>
        <div class="space-y-8">
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">How do I reset my password?</h4>
                <p class="text-slate-600 text-sm">
                    On the login screen, click "Forgot Password". Enter your email address, and a reset link will be sent to
                    your inbox immediately.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">Can I access the system from my phone?</h4>
                <p class="text-slate-600 text-sm">
                    Yes! The entire application is fully responsive and works on mobile browsers (Chrome, Safari). You can
                    create estimates and view reports on the go.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">Is there an Android/iOS app?</h4>
                <p class="text-slate-600 text-sm">
                    Currently, we operate as a Progressive Web App (PWA). You can "Add to Home Screen" from your mobile
                    browser for an app-like experience.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">How do I change my profile picture?</h4>
                <p class="text-slate-600 text-sm">
                    Navigate to <strong>Profile > Account Settings</strong> using the menu in the top right corner. Upload
                    your new photo and click Save.
                </p>
            </div>
        </div>
    </div>

    <!-- Estimates & Billing -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Estimates & Billing</h2>
        <div class="space-y-8">
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">Why can't I send an estimate?</h4>
                <p class="text-slate-600 text-sm">
                    If the "Send" button is replaced by "Request Approval", the total amount exceeds your authorization
                    limit. A manager must approve it first.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">Are expired coupons still valid?</h4>
                <p class="text-slate-600 text-sm">
                    No. Once a coupon code reaches its expiration date or usage limit, the system will reject it. You must
                    create a new code.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">Can I edit an estimate after sending?</h4>
                <p class="text-slate-600 text-sm">
                    Yes, but be careful. If the client has already viewed it, editing might cause confusion. If they've
                    already <strong>Signed</strong> it, the estimate is locked and cannot be edited.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">Are the digital signatures legally binding in India?</h4>
                <p class="text-slate-600 text-sm">
                    Yes. We comply with the Information Technology Act, 2000. We capture IP, timestamp, and audit trails to
                    ensure the validity of the e-signature.
                </p>
            </div>
        </div>
    </div>

    <!-- Technical & Workflow -->
    <div class="mb-20">
        <h2 class="text-2xl font-bold text-slate-900 mb-8">Technical & Workflow</h2>
        <div class="space-y-8">
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">Where do I find my API Key?</h4>
                <p class="text-slate-600 text-sm">
                    Go to <strong>Profile > Settings</strong> and scroll to the "API Access" section. Click "Regenerate
                    Token" if you need a new one.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">My Perfex sync isn't working.</h4>
                <p class="text-slate-600 text-sm">
                    Check your internet connection first. If the issue persists, contact IT Support. Sync logs are available
                    in the "Activity Logs" section for debugging.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">What happens if I reject an approval request?</h4>
                <p class="text-slate-600 text-sm">
                    The estimate status changes to "Rejected". The estimator receives an email notification with your
                    comments. They must revise the estimate and re-submit it.
                </p>
            </div>
            <div class="bg-white border border-slate-200 rounded-lg p-6 hover:shadow-md transition-shadow">
                <h4 class="font-bold text-slate-800 mb-2">How do I change the company logo on the PDF?</h4>
                <p class="text-slate-600 text-sm">
                    Only Super Admins can do this. Go to <strong>Admin > PDF Settings</strong> and upload a new image.
                </p>
            </div>
        </div>
    </div>
@endsection