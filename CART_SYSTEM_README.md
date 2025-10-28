# Shopping Cart System - Multi-Item Order Feature

## Overview
The CHMSU BAO system now supports ordering multiple items at once through a shopping cart feature. Students, faculty, and staff can add multiple items to their cart, review them, and place a batch order.

## Features

### For Students and Staff:
1. **Shopping Cart**
   - Add multiple items to cart before checkout
   - View cart with real-time item count badge
   - Update quantities or remove items from cart
   - Clear entire cart

2. **Multi-Item Checkout**
   - Review all items before placing order
   - See total cost for all items
   - Confirmation modal before submitting
   - Batch order ID for tracking

3. **Batch Receipts**
   - View receipt for all items in a batch order
   - Print-friendly receipt layout
   - Shows all order IDs in the batch
   - Total amount and itemized breakdown

### For Staff:
- Toggle between **Manage Inventory** and **Order Items** modes
- Order items just like students
- Continue to manage inventory in management mode

## Database Changes

### New Table: `cart`
```sql
- id (Primary Key)
- user_id (Foreign Key to user_accounts)
- inventory_id (Foreign Key to inventory)
- quantity
- size (optional, for clothing items)
- added_at (timestamp)
```

### Modified Table: `orders`
```sql
- Added: batch_id (VARCHAR) - Groups multiple orders together
- Added: size (VARCHAR) - Stores size selection for clothing
```

## Setup Instructions

### 1. Run Database Setup
Navigate to: `http://your-domain/Capstone-3/database/setup_cart_system.php`

This will:
- Create the `cart` table
- Add `batch_id` and `size` columns to `orders` table
- Create necessary indexes

### 2. File Structure
New files added:

**For Students:**
- `student/add_to_cart.php` - AJAX handler for adding items
- `student/cart.php` - Shopping cart page
- `student/checkout.php` - Checkout page
- `student/order_success.php` - Order confirmation page
- `student/batch_receipt.php` - Batch receipt viewer

**For Staff:**
- `staff/add_to_cart.php` - AJAX handler for adding items
- `staff/cart.php` - Shopping cart page
- `staff/checkout.php` - Checkout page
- `staff/order_success.php` - Order confirmation page
- (Uses student/batch_receipt.php for receipts)

**Database:**
- `database/cart_table.sql` - SQL schema
- `database/setup_cart_system.php` - Setup script

## How to Use

### For Students/Faculty/Staff:

1. **Browse Items**
   - Go to "Available Items" (students) or "Order Items" mode (staff)
   - See all available inventory items

2. **Add to Cart**
   - Click "Add to Cart" button on any item
   - Select size (if applicable) and quantity
   - Item is added to cart
   - Cart badge shows item count

3. **View Cart**
   - Click "Shopping Cart" in sidebar or "View Cart" button
   - See all items in cart
   - Update quantities or remove items

4. **Checkout**
   - Click "Proceed to Checkout"
   - Review all items
   - Confirm order
   - Receive batch order ID

5. **View Receipt**
   - After successful order, click "View Receipt"
   - Print-friendly format
   - Shows all items with individual order IDs

### For Staff (Additional):

1. **Toggle Views**
   - Switch between "Manage Inventory" and "Order Items"
   - Manage mode: Update stock, add items
   - Order mode: Shop like a student

## Features Preserved

- Single-item ordering still works via "Order Now" button
- Size selection for clothing items
- Inventory stock tracking
- Request system integration
- Order status management (pending, approved, completed)

## Technical Details

### Cart Management
- Items stored per user in `cart` table
- Unique constraint prevents duplicate items (same item + size)
- Adding same item updates quantity
- Cart persists across sessions

### Batch Orders
- All orders in a checkout get same `batch_id`
- Individual `order_id` for each item
- Single request created for all items
- Inventory updated for each item

### Size Handling
- Automatically detected for clothing keywords
- Optional for non-clothing items
- Stored in both cart and orders

## API Endpoints

### `add_to_cart.php`
- Method: POST
- Parameters: item_id, quantity, size (optional)
- Returns: JSON with success status and cart count

### Response Example:
```json
{
  "success": true,
  "message": "Item added to cart successfully",
  "cart_count": 3
}
```

## Security Features

- User authentication required
- Session validation
- SQL injection prevention (prepared statements)
- Transaction support for order processing
- Stock validation before checkout

## Benefits

1. **User Convenience**
   - Order multiple items at once
   - Review before submitting
   - Save time with batch orders

2. **Better Organization**
   - Batch order IDs group related orders
   - Easier tracking for admin
   - Consolidated receipts

3. **Flexibility**
   - Keep both single and multi-item ordering
   - Cart persists across sessions
   - Easy quantity updates

## Troubleshooting

### Cart badge not updating
- Check if JavaScript is enabled
- Verify `add_to_cart.php` endpoint is accessible
- Check browser console for errors

### Items not appearing in cart
- Ensure database setup completed successfully
- Check if user is logged in
- Verify inventory items are in stock

### Batch orders not grouping
- Check if `batch_id` column exists in orders table
- Verify transaction commits successfully
- Check database logs for errors

## Future Enhancements

Possible additions:
- Save cart for later
- Cart expiration after X days
- Email notification for batch orders
- Discount codes for bulk orders
- Wishlist feature

## Support

For issues or questions:
1. Check database setup completed successfully
2. Verify all files are in correct locations
3. Check PHP error logs
4. Ensure database connection is configured

---

**Version:** 1.0  
**Last Updated:** October 28, 2025  
**Compatible with:** CHMSU BAO System v1.0+


