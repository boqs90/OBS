import axios from 'axios';

class SearchService {
  constructor() {
    this.baseURL = 'http://localhost:8000/api';
  }

  // Obtener headers de autenticación
  getAuthHeaders() {
    const token = localStorage.getItem('authToken') || sessionStorage.getItem('authToken');
    return {
      'Authorization': `Bearer ${token}`,
      'Content-Type': 'application/json'
    };
  }

  // Búsqueda global - busca en múltiples entidades
  async globalSearch(query) {
    if (!query || query.trim().length < 2) {
      return {
        students: [],
        teachers: [],
        employees: [],
        users: [],
        enrollments: [],
        incidences: [],
        auditLogs: []
      };
    }

    try {
      const promises = [
        this.searchStudents(query),
        this.searchTeachers(query),
        this.searchEmployees(query),
        this.searchUsers(query),
        this.searchEnrollments(query),
        this.searchIncidences(query),
        this.searchAuditLogs(query)
      ];

      const results = await Promise.all(promises);
      
      return {
        students: results[0] || [],
        teachers: results[1] || [],
        employees: results[2] || [],
        users: results[3] || [],
        enrollments: results[4] || [],
        incidences: results[5] || [],
        auditLogs: results[6] || []
      };
    } catch (error) {
      console.error('Error en búsqueda global:', error);
      return {
        students: [],
        teachers: [],
        employees: [],
        users: [],
        enrollments: [],
        incidences: [],
        auditLogs: []
      };
    }
  }

  // Búsqueda de estudiantes
  async searchStudents(query) {
    try {
      const response = await axios.get(`${this.baseURL}/students`, {
        headers: this.getAuthHeaders(),
        params: { search: query, limit: 10 }
      });
      return Array.isArray(response.data) ? response.data : [];
    } catch (error) {
      console.error('Error buscando estudiantes:', error);
      return [];
    }
  }

  // Búsqueda de maestros
  async searchTeachers(query) {
    try {
      const response = await axios.get(`${this.baseURL}/teachers`, {
        headers: this.getAuthHeaders(),
        params: { search: query, limit: 10 }
      });
      return Array.isArray(response.data) ? response.data : [];
    } catch (error) {
      console.error('Error buscando maestros:', error);
      return [];
    }
  }

  // Búsqueda de empleados
  async searchEmployees(query) {
    try {
      const response = await axios.get(`${this.baseURL}/employees`, {
        headers: this.getAuthHeaders(),
        params: { search: query, limit: 10 }
      });
      return Array.isArray(response.data) ? response.data : [];
    } catch (error) {
      console.error('Error buscando empleados:', error);
      return [];
    }
  }

  // Búsqueda de usuarios
  async searchUsers(query) {
    try {
      const response = await axios.get(`${this.baseURL}/users`, {
        headers: this.getAuthHeaders(),
        params: { search: query, limit: 10 }
      });
      return Array.isArray(response.data) ? response.data : [];
    } catch (error) {
      console.error('Error buscando usuarios:', error);
      return [];
    }
  }

  // Búsqueda de matrículas
  async searchEnrollments(query) {
    try {
      const response = await axios.get(`${this.baseURL}/enrollments`, {
        headers: this.getAuthHeaders(),
        params: { search: query, limit: 10 }
      });
      return Array.isArray(response.data) ? response.data : [];
    } catch (error) {
      console.error('Error buscando matrículas:', error);
      return [];
    }
  }

  // Búsqueda de incidencias
  async searchIncidences(query) {
    try {
      const response = await axios.get(`${this.baseURL}/incidences`, {
        headers: this.getAuthHeaders(),
        params: { search: query, limit: 10 }
      });
      return Array.isArray(response.data) ? response.data : [];
    } catch (error) {
      console.error('Error buscando incidencias:', error);
      return [];
    }
  }

  // Búsqueda de logs de auditoría
  async searchAuditLogs(query) {
    try {
      const response = await axios.get(`${this.baseURL}/audit-logs`, {
        headers: this.getAuthHeaders(),
        params: { q: query, per_page: 10 }
      });
      return response.data?.data || [];
    } catch (error) {
      console.error('Error buscando logs de auditoría:', error);
      return [];
    }
  }

  // Búsqueda específica por tipo de entidad
  async searchByType(type, query) {
    switch (type) {
      case 'students':
        return this.searchStudents(query);
      case 'teachers':
        return this.searchTeachers(query);
      case 'employees':
        return this.searchEmployees(query);
      case 'users':
        return this.searchUsers(query);
      case 'enrollments':
        return this.searchEnrollments(query);
      case 'incidences':
        return this.searchIncidences(query);
      case 'audit-logs':
        return this.searchAuditLogs(query);
      default:
        return [];
    }
  }

  // Obtener sugerencias de búsqueda
  async getSearchSuggestions(query) {
    const results = await this.globalSearch(query);
    const suggestions = [];

    // Agregar sugerencias de diferentes tipos
    if (results.students.length > 0) {
      suggestions.push(...results.students.slice(0, 3).map(student => ({
        type: 'student',
        text: `${student.name} (Estudiante)`,
        id: student.id,
        route: '/profile'
      })));
    }

    if (results.teachers.length > 0) {
      suggestions.push(...results.teachers.slice(0, 3).map(teacher => ({
        type: 'teacher',
        text: `${teacher.name} (Maestro)`,
        id: teacher.id,
        route: '/profile'
      })));
    }

    if (results.employees.length > 0) {
      suggestions.push(...results.employees.slice(0, 3).map(employee => ({
        type: 'employee',
        text: `${employee.name} (Empleado)`,
        id: employee.id,
        route: '/tables'
      })));
    }

    return suggestions;
  }
}

export default new SearchService();
