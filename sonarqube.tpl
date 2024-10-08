{{- /* Template based on https://docs.sonarqube.org/latest/analysis/generic-issue/ */ -}}
{
  "rules": [
    {
      "id": "TRIVY_LOW",
      "name": "TRIVY_LOW",
      "description": "TRIVY_LOW",
      "engineId": "TRIVY",
      "cleanCodeAttribute": "IDENTIFIABLE",
      "impacts": [
        {
          "softwareQuality": "SECURITY",
          "severity": "LOW"
        }
      ]
    },
    {
      "id": "TRIVY_MEDIUM",
      "name": "TRIVY_MEDIUM",
      "description": "TRIVY_MEDIUM",
      "engineId": "TRIVY",
      "cleanCodeAttribute": "IDENTIFIABLE",
      "impacts": [
        {
          "softwareQuality": "SECURITY",
          "severity": "MEDIUM"
        }
      ]
    },
    {
      "id": "TRIVY_HIGH",
      "name": "TRIVY_HIGH",
      "description": "TRIVY_HIGH",
      "engineId": "TRIVY",
      "cleanCodeAttribute": "IDENTIFIABLE",
      "impacts": [
        {
          "softwareQuality": "SECURITY",
          "severity": "HIGH"
        }
      ]
    }
  ],
  "issues": [
  {{- $t_first := true }}
  {{- range $result := . }}
    {{- $vulnerabilityType := .Type }}
    {{- range .Vulnerabilities -}}
    {{- if $t_first -}}
      {{- $t_first = false -}}
    {{ else -}}
      ,
    {{- end }}
    {
      "ruleId": {{ if eq .Severity "UNKNOWN" -}}
                    "TRIVY_LOW"
                  {{- else if eq .Severity "LOW" -}}
                    "TRIVY_LOW"
                  {{- else if eq .Severity "MEDIUM" -}}
                    "TRIVY_MEDIUM"
                  {{- else if eq .Severity "HIGH" -}}
                    "TRIVY_HIGH"
                  {{- else if eq .Severity "CRITICAL" -}}
                    "TRIVY_HIGH"
                  {{-  else -}}
                    "TRIVY_LOW"
                  {{- end }},
      "effortMinutes": 40,
      "primaryLocation": {
        "message": "{{ .PkgName }} - {{ .VulnerabilityID }} - {{ .Title | replace "\"" "'" }}",
        "filePath": "Dockerfile"
      }
    }

    {{- end -}}
  {{- end }}
  ]
}
