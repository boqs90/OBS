import axios from 'axios';
import { getToken } from '@/utils/auth';

// Lista de maestros proporcionada
const teachers = [
  { name: 'Paula Gelsomina Maradiaga Valladares', position: 'Docente de Kinder' },
  { name: 'Cristian Adalid Madrid Sandoval', position: 'Docente 1er Grado' },
  { name: 'María José Prieto Antúnez', position: 'Docente 2do Grado' },
  { name: 'Samuel Arturo Núñez Munguia', position: 'Docente de 3er Grado' },
  { name: 'Rina Fabiola Ruíz Solís', position: 'Docente 4to Grado' },
  { name: 'Francis Elizabeth Sevilla Medina', position: 'Docente 5to Grado' },
  { name: 'Silvia Sarahí Núñez Rosales', position: 'Docente 6to Grado' },
  { name: 'María Anayansi Vallecillo Duarte', position: 'Docente 7mo Grado' },
  { name: 'Dora Alejandra Herrera López', position: 'Docente 8vo Grado' },
  { name: 'Lastenia Nicole Zelaya Lanza', position: 'Docente 9no Grado' },
  { name: 'Daylis Ivanneth Hernández Fajardo', position: 'Docente 10mo Grado' },
  { name: 'Martin Adarbin Orellana Tercero', position: 'Docente 11mo Grado' },
  { name: 'Eliezer Ariel Vaquedano Cárcamo', position: 'Docente de Arte' },
  { name: 'Yesika Nicolle Gamez Meyer', position: 'Docente de Educ. Física' },
  { name: 'Rafael Antonio Fajardo Núñez', position: 'Docente de Informática' },
  { name: 'Gerardo Joel Contreras Ruíz', position: 'Docente de Español y CCSS' },
  { name: 'Juan José Puerto Oyuela', position: 'Docente de Música' },
  { name: 'Genesis Fernanda Arias Ortéz', position: 'Maestra asistente de Kinder' },
  { name: 'Brendy Yarely Jiménez Vega', position: 'Maestra asistente de Prekinder' },
  { name: 'Jenifer Alejandra Martínez Galeas', position: 'Maestra asistente' }
];

// Función para generar datos aleatorios realistas
function generateRandomData() {
  const domains = ['gmail.com', 'yahoo.com', 'hotmail.com', 'outlook.com'];
  const phonePrefixes = ['2234', '2235', '2236', '2237', '2238', '2239'];
  
  // Generar email basado en el nombre
  const generateEmail = (fullName) => {
    const nameParts = fullName.toLowerCase().split(' ');
    const firstName = nameParts[0];
    const lastName = nameParts[nameParts.length - 1];
    const randomNum = Math.floor(Math.random() * 100);
    const domain = domains[Math.floor(Math.random() * domains.length)];
    return `${firstName}.${lastName}${randomNum}@${domain}`;
  };

  // Generar teléfono
  const generatePhone = () => {
    const prefix = phonePrefixes[Math.floor(Math.random() * phonePrefixes.length)];
    const suffix = Math.floor(Math.random() * 10000).toString().padStart(4, '0');
    return `${prefix}-${suffix}`;
  };

  // Generar número de identidad (formato hondureño)
  const generateIdentity = () => {
    const firstPart = Math.floor(Math.random() * 9000) + 1000;
    const secondPart = Math.floor(Math.random() * 9000) + 1000;
    const thirdPart = Math.floor(Math.random() * 90000) + 10000;
    return `${firstPart}-${secondPart}-${thirdPart}`;
  };

  // Generar fecha de nacimiento (entre 22 y 65 años)
  const generateBirthDate = () => {
    const currentYear = new Date().getFullYear();
    const birthYear = currentYear - Math.floor(Math.random() * 43) - 22; // 22-65 años
    const birthMonth = Math.floor(Math.random() * 12) + 1;
    const birthDay = Math.floor(Math.random() * 28) + 1;
    return `${birthYear}-${birthMonth.toString().padStart(2, '0')}-${birthDay.toString().padStart(2, '0')}`;
  };

  // Generar fecha de ingreso (entre 1 y 10 años atrás)
  const generateEntryDate = () => {
    const currentYear = new Date().getFullYear();
    const yearsAgo = Math.floor(Math.random() * 10) + 1;
    const entryYear = currentYear - yearsAgo;
    const entryMonth = Math.floor(Math.random() * 12) + 1;
    const entryDay = Math.floor(Math.random() * 28) + 1;
    return `${entryYear}-${entryMonth.toString().padStart(2, '0')}-${entryDay.toString().padStart(2, '0')}`;
  };

  return {
    email: generateEmail,
    phone: generatePhone,
    identity: generateIdentity,
    birthDate: generateBirthDate,
    entryDate: generateEntryDate
  };
}

// Función principal para ingresar maestros
async function importTeachers() {
  const authToken = getToken();
  const randomData = generateRandomData();
  
  console.log('🚀 Iniciando importación de maestros...');
  console.log(`📋 Total de maestros a procesar: ${teachers.length}`);
  
  let successCount = 0;
  let errorCount = 0;
  const errors = [];

  for (let i = 0; i < teachers.length; i++) {
    const teacher = teachers[i];
    const index = i + 1;
    
    try {
      console.log(`\n📝 Procesando ${index}/${teachers.length}: ${teacher.name}`);
      
      // Preparar datos del maestro
      const teacherData = {
        fullName: teacher.name,
        email: randomData.email(teacher.name),
        phone: randomData.phone(),
        position: teacher.position,
        birthDate: randomData.birthDate(),
        identityNumber: randomData.identity(),
        entryDate: randomData.entryDate(),
        status: 'Activo'
      };

      console.log(`   📧 Email: ${teacherData.email}`);
      console.log(`   📞 Teléfono: ${teacherData.phone}`);
      console.log(`   🆔 Identidad: ${teacherData.identityNumber}`);
      console.log(`   🎂 Nacimiento: ${teacherData.birthDate}`);
      console.log(`   📅 Ingreso: ${teacherData.entryDate}`);

      // Enviar a la API
      const response = await axios.post('http://localhost:8000/api/teachers', teacherData, {
        headers: { 
          Authorization: `Bearer ${authToken}`,
          'Content-Type': 'application/json'
        }
      });

      console.log(`   ✅ Maestro guardado con ID: ${response.data.id}`);
      successCount++;

    } catch (error) {
      console.error(`   ❌ Error al guardar maestro:`, error.response?.data || error.message);
      errorCount++;
      errors.push({
        teacher: teacher.name,
        error: error.response?.data?.message || error.message
      });
    }

    // Pequeña pausa para no sobrecargar el servidor
    await new Promise(resolve => setTimeout(resolve, 100));
  }

  // Resumen final
  console.log('\n📊 RESUMEN DE IMPORTACIÓN');
  console.log('='.repeat(50));
  console.log(`✅ Maestros importados exitosamente: ${successCount}`);
  console.log(`❌ Errores: ${errorCount}`);
  
  if (errors.length > 0) {
    console.log('\n🚨 DETALLE DE ERRORES:');
    errors.forEach((error, index) => {
      console.log(`${index + 1}. ${error.teacher}: ${error.error}`);
    });
  }

  console.log('\n🎉 Importación completada!');
}

// Ejecutar la importación
importTeachers().catch(console.error);
