-- Update Razorpay Orders Table to include user_id
-- This adds user_id column to track which user created the order

-- Simple approach: Add column directly (will fail if it already exists, but that's OK)
ALTER TABLE `razorpay_orders` 
ADD COLUMN `user_id` int(11) NOT NULL DEFAULT 0 AFTER `order_id`;

-- Add index for better performance (will fail if it already exists, but that's OK)
CREATE INDEX `idx_razorpay_orders_user_id` ON `razorpay_orders` (`user_id`);

-- Update existing records to set user_id = 1 (or appropriate default) if needed
-- UPDATE `razorpay_orders` SET `user_id` = 1 WHERE `user_id` = 0 OR `user_id` IS NULL; 

## Simple Manual SQL Commands

Instead of running the complex scripts, you can run these simple commands one by one:

### Step 1: Update Razorpay Orders Table
```sql
ALTER TABLE `razorpay_orders` 
ADD COLUMN `user_id` int(11) NOT NULL DEFAULT 0 AFTER `order_id`;
```

### Step 2: Update Job Applications Table
```sql
-- Add user_id column
ALTER TABLE `job_applications` 
ADD COLUMN `user_id` int(11) NOT NULL DEFAULT 0 AFTER `id`;

-- Add payment_order_id column
ALTER TABLE `job_applications` 
ADD COLUMN `payment_order_id` varchar(255) DEFAULT NULL AFTER `payment_id`;

-- Add application_fee column
ALTER TABLE `job_applications` 
ADD COLUMN `application_fee` decimal(10,2) DEFAULT 1000.00 AFTER `payment_order_id`;

-- Add created_at column
ALTER TABLE `job_applications` 
ADD COLUMN `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP AFTER `application_version`;

-- Add updated_at column
ALTER TABLE `job_applications` 
ADD COLUMN `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER `created_at`;
```

### Step 3: Add Indexes (Optional)
```sql
-- Add index on user_id
CREATE INDEX `idx_job_applications_user_id` ON `job_applications` (`user_id`);

-- Add index on payment_order_id
CREATE INDEX `idx_job_applications_payment_order_id` ON `job_applications` (`payment_order_id`);
```

## What These Commands Do

1. **Add `user_id` column** to both tables to track which user created the order/application
2. **Add `payment_order_id`** to link job applications with Razorpay orders
3. **Add `application_fee`** to store the fee amount
4. **Add timestamp columns** for better tracking
5. **Add indexes** for better performance

## How to Run

1. **Open your MariaDB/MySQL client** (phpMyAdmin, MySQL Workbench, or command line)
2. **Select your database** (the one containing the `razorpay_orders` and `job_applications` tables)
3. **Run each command one by one**
4. **If you get an error** saying a column already exists, that's fine - just skip that command

## After Running the Commands

Once you've added these columns, your payment system should work properly:
- ✅ User ID will be stored correctly (not as 0)
- ✅ Payment status will be updated after successful payment
- ✅ Application status will be updated to "Applied"
- ✅ All payment information will be properly linked

Try running these commands and let me know if you encounter any other issues! 