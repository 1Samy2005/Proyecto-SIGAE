# 🎓 SIGAE - Sistema Integrado de Gestión Académica Estudiantil

![PHP Version](https://img.shields.io/badge/PHP-8.0-777BB4?style=flat-square&logo=php)
![PostgreSQL](https://img.shields.io/badge/PostgreSQL-16-4169E1?style=flat-square&logo=postgresql)
![License](https://img.shields.io/badge/License-MIT-yellow.svg)
![GitHub last commit](https://img.shields.io/github/last-commit/1Samy2005/Proyecto-SIGAE)

## 📋 Descripción del Proyecto

**SIGAE** es una aplicación web desarrollada para la **U.E.N. "José Agustín Marquiegüi"** con el objetivo de modernizar y optimizar los procesos de evaluación y control de estudio. El sistema reemplaza los métodos manuales basados en hojas de cálculo por una plataforma centralizada, segura y eficiente.

> “La tecnología no es nada. Lo importante es que tengas fe en la gente, que sean básicamente buenos e inteligentes, y si les das herramientas, harán cosas maravillosas con ellas.” — *Steve Jobs*

---

## ✨ Características Principales

### 🔐 **Seguridad Avanzada**
- Autenticación de usuarios con contraseñas hasheadas (bcrypt)
- **Autenticación de Dos Factores (2FA)** con Google Authenticator
- Control de acceso basado en roles (RBAC): Administrador, Control de Estudio, Docente, Administrativo
- Protección contra inyección SQL (consultas preparadas con PDO)

### 📚 **Gestión Académica Completa**
- **Estudiantes:** CRUD completo con datos personales y lugar de nacimiento
- **Docentes:** CRUD y asignación de materias por período
- **Materias:** Catálogo de 13 materias
- **Secciones:** Gestión de grupos por año escolar
- **Períodos Académicos:** Definición de lapsos con ponderaciones

### 📊 **Calificaciones y Reportes**
- Registro de calificaciones por estudiante, materia y tipo de evaluación
- **Generación de Boletas** en PDF con logo institucional
- **Cuadros de Mérito** con ranking y podio (1°, 2°, 3°)
- **Reportes Estadísticos** con gráficos y exportación a Excel/PDF
- **Historia Académica** completa por estudiante

### 🛡️ **Mantenibilidad y Escalabilidad**
- Arquitectura MVC (Modelo-Vista-Controlador)
- Base de datos normalizada en PostgreSQL
- Código comentado y organizado
- **Backup automático** programado de la base de datos

---

## 🏗️ Arquitectura del Sistema

### Tecnologías Utilizadas
| Capa | Tecnología |
|------|------------|
| **Frontend** | HTML5, CSS3, JavaScript (AJAX) |
| **Backend** | PHP 8.0 (POO, MVC) |
| **Base de Datos** | PostgreSQL 16 |
| **Servidor** | Apache 2.4 |
| **Librerías** | Dompdf (PDF), robthree/twofactorauth (2FA) |

---

## 📁 Estructura del Proyecto

---

## ⚙️ Instalación y Configuración

### Prerrequisitos
- **XAMPP** 8.0+ (Apache + PHP) o entorno similar
- **PostgreSQL** 12 o superior
- **Composer** 2.x

### Pasos Rápidos

1. **Clonar el repositorio**
   ```bash
   git clone https://github.com/1Samy2005/Proyecto-SIGAE.git SIGAE
   cd SIGAE
   composer install
   -- Crear la base de datos en PostgreSQL
CREATE DATABASE sigaedb;
-- Ejecutar el script de creación de tablas
\i database/schema.sql

private $dbname = 'sigaedb';
private $user = 'postgres';
private $password = 'tu_contraseña';

http://localhost/SIGAE/app/views/auth/login.php

Usuario	Contraseña	Rol
admin	Admin2026!	Administrador
⚠️ ADVERTENCIA: Cambia estas credenciales inmediatamente después del primer inicio de sesión.
Diagrama de Clases Principal
@startuml
class Usuario {
    + id_usuario: int
    + nombre_usuario: string
    + email: string
    + tfa_activo: boolean
    + autenticar()
}

class Estudiante {
    + id_estudiante: int
    + cedula: string
    + nombres: string
    + apellidos: string
}

class Inscripcion {
    + id_inscripcion: int
    + anio_escolar: string
    + calcularPromedio()
}

Usuario <|-- Docente
Estudiante "1" -- "many" Inscripcion
@enduml
Ver diagramas completos en la carpeta docs/diagramas/

Pruebas Realizadas
Tipo de Prueba	Resultado
Pruebas Unitarias	✅ 100% exitosas
Pruebas de Integración	✅ Todos los módulos funcionan
Pruebas de Aceptación	✅ Usuarios satisfechos
Pruebas de Seguridad	✅ 2FA, RBAC, inyección SQL

 Próximas Mejoras
Versión responsive para dispositivos móviles

Envío de boletas por correo electrónico

Módulo de asistencia de estudiantes

Integración con sistemas externos (API)

Gráficos avanzados con Chart.js

Autores
Nombre	Rol	Contacto
Anfherny Barreto	Desarrollador Backend & Base de Datos	
Daniel Crespo	Desarrollador Frontend & Documentación	
Edgar Navarro	Arquitecto de Software & Seguridad	
Tutora: Ing. Yuly Delgado
Licencia
Este proyecto está bajo la Licencia MIT - ver el archivo LICENSE para más detalles.

Agradecimientos
A la U.E.N. "José Agustín Marquiegüi" por abrirnos las puertas y permitirnos desarrollar este proyecto. A nuestra tutora Ing. Yuly Delgado por su guía y paciencia. A todos los docentes y personal administrativo que participaron en las pruebas y nos dieron su valiosa retroalimentación.

📞 Contacto
¿Preguntas? ¿Sugerencias? ¿Quieres contribuir?

GitHub: 1Samy2005

Repositorio: Proyecto-SIGAE

⭐ Si este proyecto te fue útil, ¡no olvides darle una estrella en GitHub! ⭐
