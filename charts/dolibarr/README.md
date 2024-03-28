# Dolibarr

[Dolibarr](https://www.dolibarr.org/) is a modern software package to manage your company or foundation's activity.

**DISCLAIMER**: This is an unofficial chart not supported by Dolibarr authors.

## TL;DR;

```bash
$ helm repo add cowboysysop https://cowboysysop.github.io/charts/
$ helm install my-release cowboysysop/dolibarr
```

## Introduction

This chart bootstraps a Dolibarr deployment on a [Kubernetes](http://kubernetes.io) cluster using the [Helm](https://helm.sh) package manager.

## Prerequisites

- Kubernetes >= 1.24
- Helm >= 3.9

## Installing

Install the chart using:

```bash
$ helm repo add cowboysysop https://cowboysysop.github.io/charts/
$ helm install my-release cowboysysop/dolibarr
```

These commands deploy Dolibarr on the Kubernetes cluster in the default configuration and with the release name `my-release`. The deployment configuration can be customized by specifying the customization parameters with the `helm install` command using the `--values` or `--set` arguments. Find more information in the [configuration section](#configuration) of this document.

## Upgrading

Upgrade the chart deployment using:

```bash
$ helm upgrade my-release cowboysysop/dolibarr
```

The command upgrades the existing `my-release` deployment with the most latest release of the chart.

**TIP**: Use `helm repo update` to update information on available charts in the chart repositories.

### Upgrading to version 5.0.0

The MariaDB subchart has been updated to a major release, see these upgrade instructions:

- https://github.com/bitnami/charts/tree/master/bitnami/mariadb#to-1700
- https://github.com/bitnami/charts/tree/master/bitnami/mariadb#to-1600
- https://github.com/bitnami/charts/tree/master/bitnami/mariadb#to-1400
- https://github.com/bitnami/charts/tree/master/bitnami/mariadb#to-1300

### Upgrading to version 4.0.0

The chart is now tested with Kubernetes >= 1.24 and Helm >= 3.9.

Future upgrades may introduce undetected breaking changes if you continue to use older versions.

### Upgrading to version 3.0.0

Some parameters related to port management have been modified:

- Parameter `service.port` has been renamed `service.ports.http`.
- Parameter `service.nodePort` has been renamed `service.nodePorts.http`.

### Upgrading to version 2.0.0

Some parameters related to image management have been modified:

- Registry prefix in `image.repository` parameters is now configured in `image.registry`.
- Parameter `imagePullSecrets` has been renamed `global.imagePullSecrets`.

## Uninstalling

Uninstall the `my-release` deployment using:

```bash
$ helm uninstall my-release
```

The command deletes the release named `my-release` and frees all the kubernetes resources associated with the release.

**TIP**: Specify the `--purge` argument to the above command to remove the release from the store and make its name free for later use.

## Configuration

### Global parameters

| Name                      | Description                                     | Default |
| ------------------------- | ----------------------------------------------- | ------- |
| `global.imageRegistry`    | Global Docker image registry                    | `""`    |
| `global.imagePullSecrets` | Global Docker registry secret names as an array | `[]`    |

### Common parameters

| Name                | Description                                                                                   | Default |
| ------------------- | --------------------------------------------------------------------------------------------- | ------- |
| `kubeVersion`       | Override Kubernetes version                                                                   | `""`    |
| `nameOverride`      | Partially override `dolibarr.fullname` template with a string (will prepend the release name) | `""`    |
| `fullnameOverride`  | Fully override `dolibarr.fullname` template with a string                                     | `""`    |
| `commonAnnotations` | Annotations to add to all deployed objects                                                    | `{}`    |
| `commonLabels`      | Labels to add to all deployed objects                                                         | `{}`    |
| `extraDeploy`       | Array of extra objects to deploy with the release                                             | `[]`    |

### Parameters

| Name                                 | Description                                                                                           | Default                  |
| ------------------------------------ | ----------------------------------------------------------------------------------------------------- | ------------------------ |
| `replicaCount`                       | Number of replicas (do not change it)                                                                 | `1`                      |
| `updateStrategy.type`                | Update strategy type (do not change it)                                                               | `Recreate`               |
| `image.registry`                     | Image registry                                                                                        | `docker.io`              |
| `image.repository`                   | Image repository                                                                                      | `tuxgasy/dolibarr`       |
| `image.tag`                          | Image tag                                                                                             | `19.0.0`                 |
| `image.digest`                       | Image digest                                                                                          | `""`                     |
| `image.pullPolicy`                   | Image pull policy                                                                                     | `IfNotPresent`           |
| `pdb.create`                         | Specifies whether a pod disruption budget should be created                                           | `false`                  |
| `pdb.minAvailable`                   | Minimum number/percentage of pods that should remain scheduled                                        | `1`                      |
| `pdb.maxUnavailable`                 | Maximum number/percentage of pods that may be made unavailable                                        | `nil`                    |
| `serviceAccount.create`              | Specifies whether a service account should be created                                                 | `true`                   |
| `serviceAccount.annotations`         | Service account annotations                                                                           | `{}`                     |
| `serviceAccount.name`                | The name of the service account to use (Generated using the `dolibarr.fullname` template if not set)  | `nil`                    |
| `podAnnotations`                     | Additional pod annotations                                                                            | `{}`                     |
| `podLabels`                          | Additional pod labels                                                                                 | `{}`                     |
| `podSecurityContext`                 | Pod security context                                                                                  | `{}`                     |
| `priorityClassName`                  | Priority class name                                                                                   | `nil`                    |
| `runtimeClassName`                   | Runtime class name                                                                                    | `""`                     |
| `topologySpreadConstraints`          | Topology Spread Constraints for pod assignment                                                        | `[]`                     |
| `securityContext`                    | Container security context                                                                            | `{}`                     |
| `containerPorts.http`                | Container port for HTTP                                                                               | `80`                     |
| `livenessProbe.enabled`              | Enable liveness probe                                                                                 | `true`                   |
| `livenessProbe.initialDelaySeconds`  | Delay before the liveness probe is initiated                                                          | `180`                    |
| `livenessProbe.periodSeconds`        | How often to perform the liveness probe                                                               | `10`                     |
| `livenessProbe.timeoutSeconds`       | When the liveness probe times out                                                                     | `1`                      |
| `livenessProbe.failureThreshold`     | Minimum consecutive failures for the liveness probe to be considered failed after having succeeded    | `3`                      |
| `livenessProbe.successThreshold`     | Minimum consecutive successes for the liveness probe to be considered successful after having failed  | `1`                      |
| `readinessProbe.enabled`             | Enable readiness probe                                                                                | `true`                   |
| `readinessProbe.initialDelaySeconds` | Delay before the readiness probe is initiated                                                         | `0`                      |
| `readinessProbe.periodSeconds`       | How often to perform the readiness probe                                                              | `10`                     |
| `readinessProbe.timeoutSeconds`      | When the readiness probe times out                                                                    | `1`                      |
| `readinessProbe.failureThreshold`    | Minimum consecutive failures for the readiness probe to be considered failed after having succeeded   | `3`                      |
| `readinessProbe.successThreshold`    | Minimum consecutive successes for the readiness probe to be considered successful after having failed | `1`                      |
| `startupProbe.enabled`               | Enable startup probe                                                                                  | `false`                  |
| `startupProbe.initialDelaySeconds`   | Delay before the startup probe is initiated                                                           | `0`                      |
| `startupProbe.periodSeconds`         | How often to perform the startup probe                                                                | `10`                     |
| `startupProbe.timeoutSeconds`        | When the startup probe times out                                                                      | `1`                      |
| `startupProbe.failureThreshold`      | Minimum consecutive failures for the startup probe to be considered failed after having succeeded     | `3`                      |
| `startupProbe.successThreshold`      | Minimum consecutive successes for the startup probe to be considered successful after having failed   | `1`                      |
| `service.annotations`                | Service annotations                                                                                   | `{}`                     |
| `service.type`                       | Service type                                                                                          | `ClusterIP`              |
| `service.clusterIP`                  | Static cluster IP address or None for headless service when service type is ClusterIP                 | `nil`                    |
| `service.loadBalancerIP`             | Static load balancer IP address when service type is LoadBalancer                                     | `nil`                    |
| `service.loadBalancerSourceRanges`   | Source IP address ranges when service type is LoadBalancer                                            | `nil`                    |
| `service.externalTrafficPolicy`      | External traffic routing policy when service type is LoadBalancer or NodePort                         | `Cluster`                |
| `service.ports.http`                 | Service port for HTTP                                                                                 | `80`                     |
| `service.nodePorts.http`             | Service node port for HTTP when service type is LoadBalancer or NodePort                              | `nil`                    |
| `ingress.enabled`                    | Enable ingress controller resource                                                                    | `false`                  |
| `ingress.ingressClassName`           | IngressClass that will be be used to implement the Ingress                                            | `""`                     |
| `ingress.pathType`                   | Ingress path type                                                                                     | `ImplementationSpecific` |
| `ingress.annotations`                | Ingress annotations                                                                                   | `{}`                     |
| `ingress.hosts[0].host`              | Hostname to your Dolibarr installation                                                                | `dolibarr.local`         |
| `ingress.hosts[0].paths`             | Paths within the url structure                                                                        | `["/"]`                  |
| `ingress.tls`                        | TLS configuration                                                                                     | `[]`                     |
| `resources`                          | CPU/Memory resource requests/limits                                                                   | `{}`                     |
| `nodeSelector`                       | Node labels for pod assignment                                                                        | `{}`                     |
| `tolerations`                        | Tolerations for pod assignment                                                                        | `[]`                     |
| `affinity`                           | Map of node/pod affinities                                                                            | `{}`                     |
| `extraArgs`                          | Additional container arguments                                                                        | `{}`                     |
| `extraEnvVars`                       | Additional container environment variables                                                            | `[]`                     |
| `extraEnvVarsCM`                     | Name of existing ConfigMap containing additional container environment variables                      | `nil`                    |
| `extraEnvVarsSecret`                 | Name of existing Secret containing additional container environment variables                         | `nil`                    |
| `init.securityContext`               | Init security context                                                                                 | `{}`                     |
| `init.resources`                     | Init CPU/Memory resource requests/limits                                                              | `{}`                     |
| `persistence.enabled`                | Enable persistence using PVC                                                                          | `false`                  |
| `persistence.existingClaim`          | Name of an existing PVC to use                                                                        | `nil`                    |
| `persistence.accessMode`             | PVC access mode                                                                                       | `ReadWriteOnce`          |
| `persistence.annotations`            | PVC annotations                                                                                       | `{}`                     |
| `persistence.size`                   | PVC size                                                                                              | `1Gi`                    |
| `persistence.storageClass`           | PVC storage class                                                                                     | `nil`                    |

### Config parameters

| Name                               | Description                                                 | Default                      |
| ---------------------------------- | ----------------------------------------------------------- | ---------------------------- |
| `dolibarr.admin.username`          | Administrator username                                      | `admin`                      |
| `dolibarr.admin.password`          | Administrator password                                      | `admin`                      |
| `dolibarr.externalUrl`             | External URL                                                | `http://dolibarr.local`      |
| `dolibarr.cron.enabled`            | Enable cron for scheduled jobs                              | `false`                      |
| `dolibarr.cron.username`           | Cron username                                               | `admin`                      |
| `dolibarr.cron.securityKey`        | Cron security key                                           | `""`                         |
| `existingSecret`                   | Name of existing Secret to use                              | `""`                         |
| `existingSecretKeyAdminPassword`   | Key in existing Secret that contains administrator password | `dolibarr-admin-password`    |
| `existingSecretKeyCronSecurityKey` | Key in existing Secret that contains cron security key      | `dolibarr-cron-security-key` |

### MariaDB parameters

| Name                                        | Description                                           | Default            |
| ------------------------------------------- | ----------------------------------------------------- | ------------------ |
| `mariadb.enabled`                           | Whether to use the MariaDB chart                      | `true`             |
| `mariadb.architecture`                      | MariaDB architecture                                  | `standalone`       |
| `mariadb.auth.database`                     | MariaDB database                                      | `dolibarr`         |
| `mariadb.auth.username`                     | MariaDB user                                          | `dolibarr`         |
| `mariadb.auth.password`                     | MariaDB password                                      | `dolibarr`         |
| `mariadb.auth.existingSecret`               | Name of existing Secret to use                        | `""`               |
| `mariadb.primary.service.ports.mysql`       | MariaDB port                                          | `3306`             |
| `externalMariadb.enabled`                   | Whether to use an external MariaDB                    | `false`            |
| `externalMariadb.host`                      | External MariaDB host                                 | `mariadb`          |
| `externalMariadb.port`                      | External MariaDB port                                 | `3306`             |
| `externalMariadb.username`                  | External MariaDB user                                 | `dolibarr`         |
| `externalMariadb.password`                  | External MariaDB password                             | `dolibarr`         |
| `externalMariadb.existingSecret`            | Name of existing Secret to use                        | `""`               |
| `externalMariadb.existingSecretKeyPassword` | Key in existing Secret that contains MariaDB password | `mariadb-password` |
| `externalMariadb.database`                  | External MariaDB database                             | `dolibarr`         |

### Wait parameters

| Name                    | Description                         | Default         |
| ----------------------- | ----------------------------------- | --------------- |
| `wait.image.registry`   | Image registry                      | `docker.io`     |
| `wait.image.repository` | Image repository                    | `atkrad/wait4x` |
| `wait.image.tag`        | Image tag                           | `2.14.0`        |
| `wait.image.digest`     | Image digest                        | `""`            |
| `wait.image.pullPolicy` | Image pull policy                   | `IfNotPresent`  |
| `wait.securityContext`  | Container security context          | `{}`            |
| `wait.resources`        | CPU/Memory resource requests/limits | `{}`            |

### Tests parameters

| Name                     | Description       | Default              |
| ------------------------ | ----------------- | -------------------- |
| `tests.image.registry`   | Image registry    | `ghcr.io`            |
| `tests.image.repository` | Image repository  | `cowboysysop/pytest` |
| `tests.image.tag`        | Image tag         | `1.0.41`             |
| `tests.image.digest`     | Image digest      | `""`                 |
| `tests.image.pullPolicy` | Image pull policy | `IfNotPresent`       |

## Setting parameters

Specify the parameters you which to customize using the `--set` argument to the `helm install` command. For instance,

```bash
$ helm install my-release \
    --set nameOverride=my-name cowboysysop/dolibarr
```

The above command sets the `nameOverride` to `my-name`.

Alternatively, a YAML file that specifies the values for the above parameters can be provided while installing the chart. For example,

```bash
$ helm install my-release \
    --values values.yaml cowboysysop/dolibarr
```

**TIP**: You can use the default [values.yaml](values.yaml).
