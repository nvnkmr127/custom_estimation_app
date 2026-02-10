# SSO Administrator Guide

This guide is for system administrators responsible for configuring the connection between this application and the **Nexus Identity** (Auth Core) portal.

## 1. Requirement: Application Name (Slug)
When you register this application in the Nexus Identity portal, the **Application ID** or **Slug** must exactly match the `SSO_JWT_AUDIENCE` setting in our application. 
*   **Recommendation**: Use the full URL of this application (`https://estimator.onestudio.co.in/`) as the ID in both places.

## 2. Requirement: Callback URL
You must register the following URL as the **Allowed Callback URL** in the authentication portal:
`https://estimator.onestudio.co.in/sso/callback`

*   **Note**: This URL is case-sensitive and must use the same protocol (HTTP vs HTTPS) as the live site.

## 3. Role Assignment
Users will not be able to log in unless they have a **Role** assigned to them within the Nexus Identity portal.
*   The role name in the portal must match one of our configured "mapped" roles.
*   If a user has a role that we don't recognize, they will be given the minimum **Estimator** permissions by default.

## 4. Key Management
If the security team rotates the **Private/Public Key Pair** in Nexus Identity, you MUST immediately update the `AUTH_CORE_PUBLIC_KEY` in this application's settings, or all logins will fail with an "Invalid Token" error.

## 5. Signed Links (Portals)
The "Client Portal" links sent to your customers (for viewing and signing estimates) **do not require SSO**. Your customers do not need accounts in Nexus Identity to view their estimates; they continue to use the secure, one-click links provided in their emails.
