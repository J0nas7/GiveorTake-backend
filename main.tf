# main.tf
terraform {
  required_providers {
    scaleway = {
      source = "scaleway/scaleway"
    }
  }
  required_version = ">= 1.0.0"
}

provider "scaleway" {
  zone       = "fr-par-1"
  region     = "fr-par"
  project_id = var.scaleway_project_id
}

variable "scaleway_project_id" {
  type = string
}

variable "container_name" {
  description = "Name of the Scaleway Serverless Container"
  type        = string
}

variable "registry_image" {
  description = "Full Docker image URL (including tag)"
  type        = string
}

resource "scaleway_serverless_container" "laravel_app" {
  name           = var.container_name
  registry_image = var.registry_image
  port           = 8080
  min_scale      = 0    # Scale to zero when idle
  max_scale      = 10   # Max 10 instances under load
  memory_limit   = 256
  cpu_limit      = 140
}

output "container_url" {
  value = scaleway_serverless_container.laravel_app.url
}
