#!/bin/bash

# OBS School Management System - Database Migration Runner
# This script runs all database migrations in the correct order

# Database configuration
DB_HOST="localhost"
DB_PORT="5432"
DB_NAME="obs_production"
DB_USER="obs_user"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Function to print colored output
print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

# Check if PostgreSQL is running
print_status "Checking PostgreSQL connection..."
if ! pg_isready -h $DB_HOST -p $DB_PORT -U $DB_USER; then
    print_error "PostgreSQL is not running or not accessible"
    exit 1
fi

print_success "PostgreSQL is running"

# Check if database exists
print_status "Checking database existence..."
if ! psql -h $DB_HOST -p $DB_PORT -U $DB_USER -lqt | cut -d \| -f 1 | grep -qw $DB_NAME; then
    print_warning "Database $DB_NAME does not exist. Creating..."
    createdb -h $DB_HOST -p $DB_PORT -U $DB_USER $DB_NAME
    if [ $? -eq 0 ]; then
        print_success "Database $DB_NAME created successfully"
    else
        print_error "Failed to create database $DB_NAME"
        exit 1
    fi
else
    print_success "Database $DB_NAME exists"
fi

# Array of migration files in order
MIGRATIONS=(
    "001_create_subjects_table.sql"
    "002_create_study_plans_table.sql"
    "003_create_academic_assignments_table.sql"
    "004_create_class_schedules_table.sql"
    "005_create_communications_table.sql"
    "006_create_users_table.sql"
    "007_create_students_table.sql"
    "008_create_remaining_tables.sql"
    "009_create_diary_pedagogico_table.sql"
    "010_create_attendance_table.sql"
    "011_create_sales_and_inventory_tables.sql"
)

# Function to run a single migration
run_migration() {
    local migration_file=$1
    local migration_name=$(basename "$migration_file" .sql)
    
    print_status "Running migration: $migration_file"
    
    # Check if migration has already been run (by checking if a table exists)
    # This is a simple check - in production you might want a more sophisticated migration tracking system
    if [[ "$migration_file" == *"subjects"* ]]; then
        if psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -c "\dt subjects" | grep -q "subjects"; then
            print_warning "Table 'subjects' already exists, skipping migration"
            return 0
        fi
    fi
    
    # Run the migration
    if psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -f "$migration_file"; then
        print_success "Migration $migration_file completed successfully"
        return 0
    else
        print_error "Migration $migration_file failed"
        return 1
    fi
}

# Main execution
print_status "Starting database migrations..."

# Change to the database directory
cd "$(dirname "$0")"

# Run each migration
failed_migrations=()
for migration in "${MIGRATIONS[@]}"; do
    if [ -f "$migration" ]; then
        if ! run_migration "$migration"; then
            failed_migrations+=("$migration")
        fi
    else
        print_warning "Migration file $migration not found, skipping"
    fi
done

# Summary
echo
print_status "Migration Summary:"
echo "========================"

if [ ${#failed_migrations[@]} -eq 0 ]; then
    print_success "All migrations completed successfully!"
    echo
    print_status "Database setup is complete. You can now start the application."
else
    print_error "The following migrations failed:"
    for failed in "${failed_migrations[@]}"; do
        echo "  - $failed"
    done
    echo
    print_error "Please check the error messages above and fix any issues before proceeding."
    exit 1
fi

# Display table information
echo
print_status "Created tables:"
psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME -c "\dt" | grep -E "(subjects|study_plans|academic_assignments|class_schedules|communications|users|students|teachers|grades|diary_pedagogico|attendance|inventory_products|sales|sale_items|inventory_movements|inventory_adjustments)" | while read line; do
    echo "  - $line"
done

echo
print_status "Migration process completed!"
print_status "Next steps:"
echo "  1. Review the created tables and sample data"
echo "  2. Configure your application to connect to the database"
echo "  3. Start your application server"
echo
print_success "OBS School Management System database is ready!"
