# Sizing Feature Added to Inventory System

## What Was Changed

I've successfully added **automatic sizing detection** to the inventory management system. Now any item with clothing-related keywords will automatically show sizing options when adding or editing items.

## Changes Made

### 1. Modified `admin/inventory.php`

#### Backend Changes (PHP):
- **Add Item Functionality (Lines 29-40)**: Changed from hardcoded specific item names to flexible keyword detection
- **Edit Item Functionality (Lines 90-101)**: Same flexible keyword detection for editing items
- Keywords that trigger sizing options:
  - shirt
  - t-shirt
  - tshirt
  - pants
  - shorts
  - jacket
  - hoodie
  - polo
  - jersey

#### Frontend Changes (JavaScript):
- **Add Form (Lines 775-801)**: Updated to use keyword detection instead of specific item names
- **Edit Form (Lines 639-665)**: Shows sizing options for existing items that contain clothing keywords
- Sizing options are automatically shown/hidden based on the item name

### 2. Modified `student/order_item.php`

#### Backend Changes (PHP):
- **Order Form (Lines 183-192)**: Updated to use the same flexible keyword detection
- Now shows size selection dropdown for any clothing item
- Displays available sizes from database if saved, otherwise shows all standard sizes
- Size information is included in order details and requests

### 3. Database Column

The inventory table needs a `sizes` column to store sizing information:
- Column name: `sizes`
- Type: TEXT (stores JSON array)
- Can be NULL

## How It Works

### Adding New Items:
1. When you type an item name like "BSIT T-Shirt" or "PE Pants", the sizing checkboxes automatically appear
2. Select the sizes available for that item (XS, S, M, L, XL, 2XL, 3XL)
3. The sizes are saved as a JSON array in the database

### Editing Existing Items:
1. When you edit an item that contains clothing keywords, the sizing section automatically appears
2. If the item already has sizes saved, the appropriate checkboxes will be checked
3. You can add or modify sizes for existing items like:
   - BSIT OJT - Shirt
   - NSTP Shirt - ROTC
   - P.E - Pants
   - P.E T-Shirt

## Setup Instructions

### Step 1: Verify Database Column Exists

Run the verification script to check if the `sizes` column exists:

```
http://localhost/Capstone-3/verify_sizes_column.php
```

This script will:
- Check if the `sizes` column exists
- Automatically add it if it doesn't exist
- Show you the current table structure

### Step 2: Test the Feature (Admin Side)

1. Go to **Admin Dashboard** → **Inventory Management**
2. Click **"Add New Item"**
3. Type a name like "Test T-Shirt" and watch the sizing options appear automatically
4. Select sizes (e.g., S, M, L, XL) and add the item
5. Edit an existing item like "BSIT OJT - Shirt" and you'll see sizing options
6. Add sizes to existing items that didn't have them before

### Step 3: Test Student Ordering

1. Login as a student
2. Go to **Student Dashboard** → **Available Items**
3. Click **Order Item** on a clothing item (like "BSIT OJT - Shirt")
4. You'll see a **Size** dropdown showing the available sizes
5. Select a size and quantity, then submit the order
6. The size will be included in the order details

## Examples of Items That Will Show Sizing

Any item containing these keywords (case-insensitive):
- ✅ "BSIT OJT - Shirt" → Shows sizing
- ✅ "P.E T-Shirt" → Shows sizing
- ✅ "NSTP Shirt - ROTC" → Shows sizing
- ✅ "PE Pants" → Shows sizing
- ✅ "Basketball Jersey" → Shows sizing
- ✅ "Department Polo" → Shows sizing
- ❌ "ID Cord" → No sizing
- ❌ "Cap" → No sizing (unless you add 'cap' to the keywords list)

## Customizing Keywords

If you want to add more keywords (like 'cap', 'uniform', etc.), edit these three locations in `admin/inventory.php`:

1. **Line 32**: Add to the PHP array for the add functionality
```php
$clothingKeywords = ['shirt', 't-shirt', 'tshirt', 'pants', 'shorts', 'jacket', 'hoodie', 'polo', 'jersey', 'cap'];
```

2. **Line 93**: Add to the PHP array for the edit functionality
```php
$clothingKeywords = ['shirt', 't-shirt', 'tshirt', 'pants', 'shorts', 'jacket', 'hoodie', 'polo', 'jersey', 'cap'];
```

3. **Line 776**: Add to the JavaScript array
```javascript
const clothingKeywords = [
    'shirt',
    't-shirt',
    'tshirt',
    'pants',
    'shorts',
    'jacket',
    'hoodie',
    'polo',
    'jersey',
    'cap'
];
```

## Technical Details

### Data Storage
Sizes are stored as JSON arrays in the database:
```json
["S", "M", "L", "XL"]
```

### Available Size Options
- XS (Extra Small)
- S (Small)
- M (Medium)
- L (Large)
- XL (Extra Large)
- 2XL
- 3XL

## Troubleshooting

### Sizing doesn't appear when adding/editing items
1. Make sure the item name contains one of the clothing keywords
2. Check browser console for JavaScript errors
3. Verify the `sizes` column exists in the database (run `verify_sizes_column.php`)

### Can't save items with sizes
1. Run `verify_sizes_column.php` to ensure the `sizes` column exists
2. Check file permissions on the uploads directory
3. Review PHP error logs for database errors

## Next Steps (Optional Enhancements)

1. **Display sizes in inventory table**: Show available sizes in the main inventory listing
2. **Size-specific stock**: Track quantity for each size separately
3. **Order by size**: Allow customers to select size when ordering
4. **Size chart**: Add a size guide/chart for reference

## Summary

✅ Sizing automatically appears for clothing items
✅ Works for both new and existing items  
✅ Flexible keyword-based detection
✅ Easy to customize keywords
✅ Stores sizes as JSON for flexibility

