<!--
  - SPDX-FileCopyrightText: 2020-2024 Nextcloud GmbH and Nextcloud contributors
  - SPDX-License-Identifier: AGPL-3.0-or-later
-->
# Rest API documentation of Nextcloud Notes

The notes app comes with a REST-based API.
It can be used in order to provide full access to notes from third-party apps.
This documentation describes the API in detail.
In this file, general information about the API is provided.
The endpoint specification for a specific version can be found in separate files (see table below).


## Major API versions

| API version | Supported by app version | Documentation  |
|:-----------:|:-------------------------|:--------|
|  **1**      | since Notes 3.3 (May 2020)     | **[Documentation](v1.md)** |
|  **0.2**    | since Notes 1.0 (2015)         | *(deprecated)* |


## Versioning policy

While the notes app evolves and receives new features, it may be required to adopt the API.
As far as possible, we try to make these changes backward compatible.
However, this is not always possible.
Therefore, a versioning scheme is realized in order to not break clients using an older API version.
We distinguish major and minor versions:

- a major version comes with changes that are incompatible to the previous version and therefore would break old clients. Major versions come with a new base URL path.
- a minor version has changes that are realized compatible to the previous version. Old clients can still use the current API endpoint, but they need adoption in order to use new features.

### Compability between minor versions

Minor versions of the same major version use the same API endpoint (path). Therefore, they must be compatible.

In order to realize forward compatibility between minor versions, clients must follow some general rules regarding the API:

- when processing the JSON response, unknown fields must be ignored (e.g. if API version 1.0 does not define the note's attribute "tags", a client must ignore such an unkown field in order to be compatible with a possible future version (e.g. 1.4) which defines such a field)
- when processing the HTTP response code, a client must be able to handle newly introduced error codes (e.g. if API 1.0 does not explicitly define response code 405, the client must handle it at least like 400; same with a 5xx code).

In order to realize backwards compatibility between minor versions, a client must follow the following rules:

- when sending a request which uses a feature that wasn't available from beginning of the used major version, the client has to cope with the situation that the server ignores parts of the request
- when processing the JSON response, the server may ommit fields that where not available from beginning of the used major version

If a client requires a certain feature, it should check the list of supported API version from server (see *Capabilities*).


### Capabilites

From Notes app version 3.3, supported API versions can be queried using the [Nextcloud Capabilities API](https://docs.nextcloud.com/server/latest/developer_manual/client_apis/OCS/ocs-api-overview.html#capabilities-api).

A request like

	curl -u user:password -X GET -H "OCS-APIRequest: true" -H "Accept: application/json" https://yournextcloud.com/ocs/v2.php/cloud/capabilities 

will return the following result (in this example, irrelevant attributes are omitted and formatting was introduced):

```json
{
  "ocs": {
    "data": {
      "capabilities": {
        "notes": {
          "api_version": [ "0.2", "1.0" ],
          "version": "3.6.0"
        }
      }
    }
  }
}
```

|  Attribute    | Type            | Description | since app version |
|:--------------|:----------------|:------------|:------------------|
| `api_version` | list of strings | list of supported API version; for each supported major API version, the highest supported minor API version is listed, e.g. `[ "0.2", "1.1" ]`  | Notes 3.3 |
| `version`     | string          | app version, e.g. `"3.6.0"`  | Notes 3.6 |

From Notes app version 3.3, the list of supported API versions is also provided in every response from the Notes API.
For this, the HTTP header `X-Notes-API-Versions` is used.
It contains a coma-separated list of versions, e.g., `X-Notes-API-Versions: 0.2, 1.0`.

### Processing API version information
In order to be compatible to older Notes version, you may want to implement multiple API versions in your client application.
In this case, you should periodically request OCS capabilities from the server or parse the `X-Notes-API-Versions` HTTP header.
Your application must then walk through the list of supported API versions and for each version do:
1. parse the version string (e.g. `1.2`) and gather the major version (here: `1`) as well as the minor version (here: `2`)
2. check if your client app supports the major version and then check if the minor version is greater or equal than your minimum required minor version for that major version

Then use the highest API version to which this requirement applies.

## Authentication

Because REST is stateless you have to send user and password each time you access the API.
Therefore running Nextcloud **with SSL is highly recommended** otherwise **everyone in your network can log your credentials**.

You can test your request using `curl`:

    curl -u user:password -H "Accept: application/json" https://yournextcloud.com/index.php/apps/notes/api/v1/notes

If you have enabled two-factor authentication you will have to create an app specific password for accessing the API. Please see [Nextcloud documentation](https://docs.nextcloud.com/server/latest/user_manual/session_management.html) for further details.

## Input parameters

In general the input parameters can be in the URL or request body. The app framework doesn't differentiate between them.

Therefore, JSON in the request body like:
```js
{
  "id": 3
}
```
will be treated the same as

    /?id=3

It is recommended though that you use the following convention:

* **GET**: parameters in the URL
* **POST**: parameters as JSON in the request body
* **PUT**: parameters as JSON in the request body
* **DELETE**: parameters as JSON in the request body

The output is JSON.
