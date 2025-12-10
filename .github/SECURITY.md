# Security Policy

> **⚠️ Demo/Prototype Notice:** This is a demonstration/prototype system. Security vulnerabilities should be expected and addressed before any production deployment.

## Supported Versions

We actively support security updates for the following versions:

| Version | Supported          | Notes                |
| ------- | ------------------ | -------------------- |
| 1.0.x   | :white_check_mark: | Demo/Prototype only  |

## Reporting a Vulnerability

If you discover a security vulnerability, please **do not** open a public issue. Instead, please report it privately by:

1. Email: [Your security email]
2. Include details about the vulnerability
3. Include steps to reproduce (if applicable)
4. We will respond within 48 hours

## Security Best Practices

- Always use HTTPS in production
- Keep your `.env` file secure and never commit it
- Use strong, unique passwords
- Regularly update dependencies: `composer update`
- Keep PHP and MySQL updated to latest stable versions
- Review and rotate JWT secrets regularly
- Use environment variables for all sensitive configuration

## Known Security Considerations

- Change default admin credentials immediately after installation
- Use strong JWT secrets (minimum 32 characters)
- Implement rate limiting for API endpoints in production
- Regularly audit user permissions and roles
- Keep vendor dependencies updated

