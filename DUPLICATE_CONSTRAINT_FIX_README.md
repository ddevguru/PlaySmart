# Fix for "Duplicate entry '0-34' for key 'user_job_unique'" Error

## Problem Description

The error "Duplicate entry '0-34' for key 'user_job_unique'" occurs when trying to submit a job application. This happens because:

1. **Unique Constraint**: The `job_applications` table has a unique constraint `user_job_unique` on the combination of `user_id` and `job_id`
2. **Existing Records**: There are existing records in the database with `user_id = 0` (probably from old data migration)
3. **Constraint Violation**: When a user tries to apply for job ID 34, the system attempts to insert a record with `user_id = 0` and `job_id = 34`, but this combination already exists

## Root Cause

The issue stems from database schema evolution:

- Originally, the `job_applications` table didn't have a `user_id` column
- Later, a `user_id` column was added with a default value of 0
- A unique constraint `user_job_unique` was added on `(user_id, job_id)`
- Existing records still have `user_id = 0`, causing conflicts when new applications are submitted

## Solution

### Option 1: Run the PHP Fix Script (Recommended)

1. **Run the fix script**:
   ```bash
   php fix_duplicate_user_job_constraint.php
   ```

2. **Follow the web interface** to:
   - Analyze the current state
   - Update existing records with proper user IDs
   - Create placeholder users for unmatched emails
   - Verify the fix

### Option 2: Run the SQL Script Directly

1. **Execute the SQL script** in your database:
   ```sql
   source fix_user_job_constraint.sql
   ```

2. **Or run the commands manually**:
   ```sql
   -- Update records where we can find matching users
   UPDATE job_applications ja
   JOIN users u ON ja.email = u.email
   SET ja.user_id = u.id
   WHERE ja.user_id = 0 AND u.id IS NOT NULL;
   
   -- Create placeholder users for unmatched emails
   INSERT IGNORE INTO users (email, name, status, created_at)
   SELECT DISTINCT ja.email, ja.student_name, 'inactive', NOW()
   FROM job_applications ja
   LEFT JOIN users u ON ja.email = u.email
   WHERE ja.user_id = 0 AND u.id IS NULL;
   
   -- Update job applications with newly created users
   UPDATE job_applications ja
   JOIN users u ON ja.email = u.email
   SET ja.user_id = u.id
   WHERE ja.user_id = 0 AND u.id IS NOT NULL;
   ```

## What the Fix Does

1. **Identifies Problem Records**: Finds all job applications with `user_id = 0`
2. **Matches Existing Users**: Links applications to existing user accounts by email
3. **Creates Placeholder Users**: For unmatched emails, creates inactive user accounts
4. **Updates All Records**: Ensures every job application has a valid `user_id`
5. **Verifies the Fix**: Checks that no duplicate constraints remain

## Prevention

The submission script (`submit_job_application_new.php`) has been updated with additional validation:

```php
// Additional validation: ensure user_id is valid
if (empty($userId) || $userId <= 0) {
    throw new Exception('Invalid user ID. Please log in again.');
}
```

This prevents future submissions with invalid user IDs.

## Verification

After running the fix, verify that:

1. **No records with `user_id = 0`**:
   ```sql
   SELECT COUNT(*) FROM job_applications WHERE user_id = 0;
   -- Should return 0
   ```

2. **No duplicate user_id + job_id combinations**:
   ```sql
   SELECT user_id, job_id, COUNT(*) as count
   FROM job_applications
   WHERE user_id > 0
   GROUP BY user_id, job_id
   HAVING COUNT(*) > 1;
   -- Should return no rows
   ```

3. **Unique constraint is working**:
   ```sql
   SHOW CREATE TABLE job_applications;
   -- Should show the user_job_unique constraint
   ```

## Files Created/Modified

- **`fix_duplicate_user_job_constraint.php`** - PHP script with web interface
- **`fix_user_job_constraint.sql`** - Direct SQL commands
- **`submit_job_application_new.php`** - Enhanced with validation
- **`DUPLICATE_CONSTRAINT_FIX_README.md`** - This documentation

## After the Fix

Once the fix is applied:

1. **Users can apply for jobs** without encountering the duplicate entry error
2. **All existing applications** are properly linked to user accounts
3. **The unique constraint** works as intended, preventing duplicate applications
4. **Future submissions** are validated to ensure proper user authentication

## Troubleshooting

If you encounter issues:

1. **Check database connection** in `db_config.php`
2. **Verify table structure** matches the expected schema
3. **Check user permissions** for UPDATE and INSERT operations
4. **Review error logs** for specific database errors

## Support

If you need additional help:

1. **Run the diagnostic script** to see detailed information
2. **Check the database logs** for specific error messages
3. **Verify the table structure** matches the expected schema
4. **Ensure all required columns** exist in the `job_applications` table 