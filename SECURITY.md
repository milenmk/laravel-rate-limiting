# Security Policy

## Supported Versions

| Version | Stage       | Environment      | Supported Until |
| ------- | ----------- | ---------------- | --------------- |
| 1.x.x   | Development | Internal Testing | TBD             |

- Development: Used for features under development; not recommended for production.
- Stable: Deployed to production for all users.

_Supported Until_ dates will be updated as the development lifecycle progresses.

## Supported vulnerabilities

We handle vulnerabilities related to the following:

- Authentication and authorization issues
- Cross-site scripting (XSS)
- SQL injection
- Sensitive data exposure
- Other critical or high-severity issues

## Reporting a Vulnerability

To report a vulnerability, please email **support@minkov.dev**. This email is exclusively for security-related
reports. The first reply will be within 24 hours.

Expected time to fix any reported vulnerability:

- minor fixes: 24-72 hours.
- complex fixes: 1-4 weeks

For urgent vulnerabilities (e.g., critical data exposure or platform compromise), we will initiate an immediate
investigation and update impacted users as soon as possible.

If you want to be notified when the reported vulnerability is resolved, please state this explicitly in the reporting
email.

## Disclosure of vulnerabilities

We request that vulnerabilities remain confidential for a reasonable time after a fix is applied, to allow our users to
take appropriate steps, if necessary.

We define "reasonable time" based on severity:

- High/Critical: 1 month
- Medium: 2 months
- Low: 3 months

## Security Update Process

After a vulnerability is fixed, we will:

1. Announce the fix on our website.
2. Notify affected users directly if the vulnerability impacts their data or security. Notifications will be sent within
   72 hours of confirming the impact.
3. Provide instructions if any user action is required, such as password resets or API key regeneration.

## Data Protection

In the event of a vulnerability that may affect user data, we prioritize the following actions:

1. Isolate and secure affected systems to prevent further impact.
2. Assess whether any data exposure or unauthorized access occurred.
3. Notify affected users promptly if their data was impacted, per legal and regulatory requirements.
4. Provide guidance on mitigation steps, if necessary.

## Proactive Security Practices

We are committed to maintaining a secure SaaS platform. Our security measures include:

- Continuous monitoring for suspicious activity and vulnerabilities.
- Immediate deployment of security patches to all servers.
- Secure development practices, including code reviews and automated testing.

## Responsible Disclosure Program

We recognize and appreciate the efforts of security researchers. If your report leads to a confirmed vulnerability fix,
we may offer a public acknowledgment on our website or in our release notes (with your consent).
