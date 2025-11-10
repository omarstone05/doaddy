# Production Deployment Checklist

## Pre-Deployment

- [ ] Review and update `.env` with production values
- [ ] Generate new `APP_KEY` (`php artisan key:generate`)
- [ ] Set `APP_DEBUG=false`
- [ ] Set `APP_ENV=production`
- [ ] Configure production database credentials
- [ ] Set up Redis for caching and queues
- [ ] Configure email/SMTP settings
- [ ] Set `ADMIN_DEFAULT_PASSWORD` in `.env` (or change after first login)
- [ ] Review all environment variables

## Security

- [ ] Change default admin password immediately after deployment
- [ ] Verify `.env` file is not publicly accessible
- [ ] Set proper file permissions (644 for files, 755 for directories)
- [ ] Set storage and cache permissions (775)
- [ ] Configure SSL/TLS certificates
- [ ] Review and test CSRF protection
- [ ] Enable rate limiting
- [ ] Configure firewall rules
- [ ] Remove or secure test credentials
- [ ] Review API keys and secrets

## Database

- [ ] Run all migrations (`php artisan migrate --force`)
- [ ] Seed admin user and settings (`php artisan db:seed --class=AdminSeeder`)
- [ ] Set up automated database backups
- [ ] Test database connection
- [ ] Verify database user has correct permissions

## Optimization

- [ ] Cache configuration (`php artisan config:cache`)
- [ ] Cache routes (`php artisan route:cache`)
- [ ] Cache views (`php artisan view:cache`)
- [ ] Build production assets (`npm run build`)
- [ ] Enable OPcache in PHP
- [ ] Configure Redis properly
- [ ] Set up CDN for static assets (optional)

## Server Configuration

- [ ] Configure web server (Nginx/Apache)
- [ ] Set up SSL/TLS
- [ ] Configure PHP-FPM
- [ ] Set up queue workers (Supervisor)
- [ ] Configure cron for scheduled tasks
- [ ] Set up log rotation
- [ ] Configure monitoring/alerting

## Post-Deployment

- [ ] Test login functionality
- [ ] Change default admin password
- [ ] Configure AI provider in System Settings
- [ ] Test AI connection
- [ ] Configure email settings
- [ ] Test email sending
- [ ] Verify all features work correctly
- [ ] Test support ticket system
- [ ] Verify dashboard loads correctly
- [ ] Test Addy AI chat functionality

## Monitoring

- [ ] Set up error tracking (e.g., Sentry)
- [ ] Configure application monitoring
- [ ] Set up uptime monitoring
- [ ] Monitor queue workers
- [ ] Set up database monitoring
- [ ] Configure log aggregation
- [ ] Set up alerting for critical issues

## Documentation

- [ ] Document production URLs
- [ ] Document admin credentials (securely)
- [ ] Document backup procedures
- [ ] Document rollback procedures
- [ ] Document emergency contacts
- [ ] Create runbook for common issues

## Testing

- [ ] Test user registration/login
- [ ] Test all main features
- [ ] Test admin panel
- [ ] Test support tickets
- [ ] Test AI functionality
- [ ] Test email notifications
- [ ] Test file uploads
- [ ] Test queue processing
- [ ] Load testing (optional)

## Backup & Recovery

- [ ] Set up automated database backups
- [ ] Set up file storage backups
- [ ] Test backup restoration
- [ ] Document backup schedule
- [ ] Set up off-site backups
- [ ] Test disaster recovery procedures

## Final Checks

- [ ] All tests passing
- [ ] No errors in logs
- [ ] Performance is acceptable
- [ ] Security scan completed
- [ ] All credentials changed
- [ ] Documentation updated
- [ ] Team notified of deployment

---

**Deployment Date**: _______________
**Deployed By**: _______________
**Version**: _______________

