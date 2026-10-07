<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400"></a></p>

<p align="center">
<a href="https://travis-ci.org/laravel/framework"><img src="https://travis-ci.org/laravel/framework.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## AutoSync bulk SMS

To enable AutoSync for the existing SMS purchase endpoint, set the `BULKSMS`
subcategory's **description** (provider) to `AUTOSYNC` in the admin category
editor. Keep its existing products/rates configured; pricing is per recipient
per 160-character segment. `SMARTSMS` and `BULKSMSNG` remain available.

Configure the server environment:

```dotenv
AUTOSYNC_SMS_BASE_URL=https://autosyncng.com/api/v1
AUTOSYNC_SMS_API_TOKEN=your-api-token
AUTOSYNC_SMS_PIN=your-four-digit-transaction-pin
AUTOSYNC_SMS_WEBHOOK_URL=https://swiftlinkng.com/autosync-webhook
```

The token and base URL fall back to `autoSyncToken` and `autoSyncUrl` if the
SMS-specific variables are absent. The webhook URL defaults to
`APP_URL/autosync-webhook`. Use an AutoSync-approved sender ID. After changing
configuration, rebuild Laravel's configuration cache (`php artisan config:cache`).

Requests support 1–1000 Nigerian numbers, a message up to 1000 characters and
a sender ID up to 20 characters. Validation runs before wallet debit. Each
bulk request uses the order reference as AutoSync's `request_ref` idempotency
key. Confirmed failures use the existing wallet refund flow; pending, timeout,
server-error and unrecognized responses remain pending. The existing
`AutoSyncWebhookJob` handles final successful/failed callbacks; ensure the
webhook is reachable and the queue worker is running. If no callback arrives,
reconcile the pending order with AutoSync before refunding or resending.

Provider documentation: [SMS options](https://autosyncng.com/user/developer/docs?path=v1%2Fsms%2Fget.json),
[send SMS](https://autosyncng.com/user/developer/docs?path=v1%2Fsms%2Fpost.json),
[authentication](https://autosyncng.com/user/developer/docs?path=v1%2Fmisc%2Fauth.json).

Run the HTTP-faked integration tests with
`php vendor/bin/phpunit tests/Feature/AutoSyncSMSServiceTest.php`.
The application's test bootstrap requires its configured database; these tests
do not send live SMS.

## About Laravel

Laravel is a web application framework with expressive, elegant syntax. We believe development must be an enjoyable and creative experience to be truly fulfilling. Laravel takes the pain out of development by easing common tasks used in many web projects, such as:

- [Simple, fast routing engine](https://laravel.com/docs/routing).
- [Powerful dependency injection container](https://laravel.com/docs/container).
- Multiple back-ends for [session](https://laravel.com/docs/session) and [cache](https://laravel.com/docs/cache) storage.
- Expressive, intuitive [database ORM](https://laravel.com/docs/eloquent).
- Database agnostic [schema migrations](https://laravel.com/docs/migrations).
- [Robust background job processing](https://laravel.com/docs/queues).
- [Real-time event broadcasting](https://laravel.com/docs/broadcasting).

Laravel is accessible, powerful, and provides tools required for large, robust applications.

## Learning Laravel

Laravel has the most extensive and thorough [documentation](https://laravel.com/docs) and video tutorial library of all modern web application frameworks, making it a breeze to get started with the framework.

If you don't feel like reading, [Laracasts](https://laracasts.com) can help. Laracasts contains over 2000 video tutorials on a range of topics including Laravel, modern PHP, unit testing, and JavaScript. Boost your skills by digging into our comprehensive video library.

## Laravel Sponsors

We would like to extend our thanks to the following sponsors for funding Laravel development. If you are interested in becoming a sponsor, please visit the Laravel [Patreon page](https://patreon.com/taylorotwell).

### Premium Partners

- **[Vehikl](https://vehikl.com/)**
- **[Tighten Co.](https://tighten.co)**
- **[Kirschbaum Development Group](https://kirschbaumdevelopment.com)**
- **[64 Robots](https://64robots.com)**
- **[Cubet Techno Labs](https://cubettech.com)**
- **[Cyber-Duck](https://cyber-duck.co.uk)**
- **[Many](https://www.many.co.uk)**
- **[Webdock, Fast VPS Hosting](https://www.webdock.io/en)**
- **[DevSquad](https://devsquad.com)**
- **[Curotec](https://www.curotec.com/services/technologies/laravel/)**
- **[OP.GG](https://op.gg)**
- **[WebReinvent](https://webreinvent.com/?utm_source=laravel&utm_medium=github&utm_campaign=patreon-sponsors)**
- **[Lendio](https://lendio.com)**

## Contributing

Thank you for considering contributing to the Laravel framework! The contribution guide can be found in the [Laravel documentation](https://laravel.com/docs/contributions).

## Code of Conduct

In order to ensure that the Laravel community is welcoming to all, please review and abide by the [Code of Conduct](https://laravel.com/docs/contributions#code-of-conduct).

## Security Vulnerabilities

If you discover a security vulnerability within Laravel, please send an e-mail to Taylor Otwell via [taylor@laravel.com](mailto:taylor@laravel.com). All security vulnerabilities will be promptly addressed.

## License

The Laravel framework is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
