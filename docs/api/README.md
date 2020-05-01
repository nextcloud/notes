# API documentation

The notes app comes with a REST-based API.
It can be used in order to provide full access to notes from third-party apps.
This documentation describes the API in detail.
In this file, general information about the API is provided.
The endpoint specification for a specific version can be found in separate files (see table below).


## Major API versions

| API version | Supported by app version | Remarks |
|:-----------:|--------------------------|---------|
|  **1**      | since 3.3 (May 2020)     | **[documentation](v1.md)** |
|  **0.2**    | since 1.0 (2015)         | *(deprecated)* |


## Versions and Capabilites

While the notes app evolves and receives new features, it may be required to adopt the API.
As far as possible, we try to make these changes backward compatible.
However, this is not always possible.
Therefore, a versioning scheme is realized in order to not break clients using an older API version.
We distinguish major and minor versions:

- a major version comes with changes that are incompatible to the previous version and therefore would break old clients. Major versions come with a new base URL path.
- a minor version has changes that are realized compatible to the previous version. Old clients can still use the current API endpoint, but they need adoption in order to use new features.

From Notes app version 3.3, supported API versions can be queried using the [Nextcloud Capabilities API](https://docs.nextcloud.com/server/latest/developer_manual/client_apis/OCS/ocs-api-overview.html#capabilities-api).

A request like

	curl -u user:password -X GET -H "OCS-APIRequest: true" -H "Accept: application/json" https://yournextcloud.com/ocs/v2.php/cloud/capabilities 

will return the following result (irrelevant attributes are omitted):

```json
{
  "ocs": {
    "data": {
      "capabilities": {
        "notes": {
          "api_version": [ "0.2", "1.0" ]
        }
      }
    }
  }
}
```

From Notes app version 3.3, the list of supported API versions is also provided in every response from the Notes API.
For this, the HTTP header `X-Notes-API-Versions` is used.
It contains a coma-separated list of versions, e.g., `X-Notes-API-Versions: 0.2, 1.0`.


## Compability between minor versions

In order to realize forward compatibility between minor versions, clients must follow some general rules regarding the API:

- when processing the JSON response, unknown fields must be ignored (e.g. if API version 1.0 does not define the note's attribute "tags", a client must ignore such an unkown field in order to be compatible with a possible future version (e.g. 1.4) which defines such a field)
- when processing the HTTP response code, a client must be able to handle newly introduced error codes (e.g. if API 1.0 does not explicitly define response code 405, the client must handle it at least like 400; same with a 5xx code).

In order to realize backwards compatibility between minor versions, a client must follow the following rules:

- when sending a request which uses a feature that wasn't available from beginning of the used major version, the client has to cope with the situation that the server ignores parts of the request
- when processing the JSON response, the server may ommit fields that where not available from beginning of the used major version

If a client requires a certain feature, it should check the `X-Notes-API-Versions` HTTP header of the server response.


## Authentication

Because REST is stateless you have to send user and password each time you access the API.
Therefore running Nextcloud **with SSL is highly recommended** otherwise **everyone in your network can log your credentials**.

You can test your request using `curl`:

    curl -u user:password https://yournextcloud.com/index.php/apps/notes/api/v1/notes


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
