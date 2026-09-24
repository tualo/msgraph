# msgraph

PHP-Integration fuer Microsoft Graph innerhalb der Tualo-Office-Anwendung.

## Installation

Das Paket wird im Host-Projekt ueber Composer installiert:

```bash
composer require tualo/msgraph
```

Composer registriert dabei den Namespace `Tualo\Office\MSGraph\` fuer das
Verzeichnis `src/` und bindet `src/functions.php` automatisch ein. Die
Microsoft-Graph-Abhaengigkeit (`microsoft/microsoft-graph` ab `2.32.0`) wird
ebenfalls installiert.

## Setup

Nach der Installation sind die folgenden Schritte erforderlich:

1. Die SQL-Definitionen des Pakets ueber den migrations-/setup-Mechanismus der
	Tualo-Office-Anwendung einspielen. Dadurch werden unter anderem die
	Graph-Scopes und Webhook-Datenbanktabellen angelegt.
2. In Microsoft Entra ID eine App-Registrierung anlegen und fuer die
	benoetigten Microsoft-Graph-APIs die passenden delegierten oder
	Application-Berechtigungen vergeben. Die Berechtigungen muessen anschliessend
	administrativ genehmigt werden, sofern Microsoft dies verlangt.
3. `tenantId` und `clientId` in der MS-Graph-Konfiguration hinterlegen. Die
	Setup-Route `/msgraph/setup/apiconfig` akzeptiert diese Werte per `POST` als
	`tenant_id` und `client_id`.
4. Fuer den App-only-Zugriff zusaetzlich ein Client Secret hinterlegen und
	`defaultUserId` setzen. Das Secret wird nicht im Quellcode gespeichert.
5. Die benoetigten Scopes in `msgraph_scope` pruefen bzw. ergaenzen. Die
	Standardwerte werden durch `src/sql/msgraph_scope.sql` angelegt.

### Authentifizierung

Das Paket unterstuetzt zwei Betriebsarten:

- **Benutzerzugriff:** Ohne `clientSecret` wird der Benutzerzugriff verwendet.
  Jeder Benutzer authentifiziert sich selbst; Token werden benutzerbezogen
  gespeichert.
- **App-only:** Mit `clientSecret` wird der Client-Credentials-Flow verwendet.
  Dafuer muessen `tenantId`, `clientId`, `clientSecret` und `defaultUserId`
  konfiguriert sein. Der Zugriff erfolgt anschliessend fuer den konfigurierten
  Benutzer.

Die Konfigurationswerte werden in den Tabellen `msgraph_setup`,
`msgraph_environments` und `msgraph_app_environments` verwaltet. Die Anwendung
benoetigt ausserdem eine aktive Session-Datenbank, da Tokens und
benutzerspezifische Einstellungen dort abgelegt werden.

## Verwendung

Nach erfolgreichem Setup stehen die registrierten Routen unter `/msgraph/*`
zur Verfuegung. Der PHP-Namespace lautet:

```php
use Tualo\Office\MSGraph\API;

$profile = API::getMe();
```
