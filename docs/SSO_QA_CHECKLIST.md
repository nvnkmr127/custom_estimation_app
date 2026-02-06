# SSO Manual QA Checklist

This checklist provides step-by-step instructions for manually validating the Single Sign-On (SSO) integration between this application and **Nexus Identity** (Auth Core).

## 1. Prerequisites
- [ ] `AUTH_SSO_ENABLED=true` is set in `.env`.
- [ ] `AUTH_CORE_URL` points to the correct Nexus Identity environment.
- [ ] `AUTH_CORE_PUBLIC_KEY` matches the public key provided by Nexus Identity.
- [ ] A valid test user exists in Nexus Identity.

## 2. Authentication Flow

### Case 2.1: Guest Redirection
- [ ] Open a fresh browser window (Incognito/Private recommended).
- [ ] Navigate to `/dashboard`.
- [ ] **Expectation**: Automatically redirected to `Nexus Identity` login page.
- [ ] **Expectation**: The `redirect` query parameter in the URL matches your current application URL.

### Case 2.2: Successful Login
- [ ] On the Nexus Identity page, enter valid credentials.
- [ ] Click login.
- [ ] **Expectation**: Page redirects back to the application `/sso/callback`.
- [ ] **Expectation**: Application processes the token and redirects to `/dashboard`.
- [ ] **Expectation**: User name and role are correctly displayed in the application navigation bar.

### Case 2.3: Session Persistence
- [ ] After a successful login, navigate to different pages (e.g., `/estimates`, `/clients`).
- [ ] Close the browser tab and reopen it to the application URL.
- [ ] **Expectation**: You remain authenticated and can access protected pages until the session expires or you log out.

## 3. Account Provisioning & Roles

### Case 3.1: First-Time Login (Auto-Provisioning)
- [ ] Log in with an SSO account that has **never** been used in this application before.
- [ ] **Expectation**: A new local user account is created automatically.
- [ ] **Expectation**: The user's role is correctly mapped based on the `role` claim in the SSO token.

### Case 3.2: Account Linking
- [ ] Manually create a local user with the email `tester@example.com`.
- [ ] Log in via SSO using an account with the same email.
- [ ] **Expectation**: The SSO login succeeds and links to the existing account without duplication.

## 4. Logout Synchronization

### Case 4.1: Synchronized Logout
- [ ] Click the **Logout** button in the application.
- [ ] **Expectation**: Local session is cleared.
- [ ] **Expectation**: Redirected to Nexus Identity logout page (if `SSO_CENTRALIZED_LOGOUT=true`).

## 5. Security Edge Cases

### Case 5.1: Token Replay
- [ ] Attempt to reuse an SSO callback URL from your browser history.
- [ ] **Expectation**: The application rejects the request with an `HTTP 403 Forbidden` error (Replay Protection).

### Case 5.2: SSO Disabled
- [ ] Set `AUTH_SSO_ENABLED=false` in `.env`.
- [ ] Attempt to access `/dashboard`.
- [ ] **Expectation**: Redirected to the **local** login page, not the SSO provider.
