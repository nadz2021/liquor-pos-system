# Liquor Store POS System Description

## Overview
This Liquor Store POS System is a custom-built point-of-sale and inventory management solution designed for small to medium liquor stores. It helps store owners, managers, and cashiers handle daily sales, monitor stock, manage products, and keep transaction records in one system.

The system is built using Pure PHP MVC with a MySQL database and runs in a Docker environment for easier setup and deployment.

## Main Purpose
The purpose of the system is to simplify store operations by combining sales processing, product management, stock monitoring, refunds, category organization, and user access control into one platform.

## Key Features

### 1. Point of Sale
- Barcode scanning
- Manual product search or selection
- Add to cart and adjust item quantity
- Multiple payment methods:
  - Cash
  - GCash
  - Gift Card
  - Store Credit
  - Card Terminal
- Automatic sales recording
- Change computation
- Sales history tracking

### 2. Product Management
- Add, edit, activate, and deactivate products
- Upload product image
- Product description
- Unique barcode handling
- Category and subcategory assignment
- Price and stock management
- Reorder point and low stock threshold

### 3. Category and Subcategory Management
- Main category management
- Subcategory management linked to a main category
- Description field for categories and subcategories

### 4. User Management
- Role-based access control
- Supported roles:
  - Super Admin
  - Admin
  - Owner
  - Manager
  - Cashier
- Add, edit, deactivate users
- Reset PIN
- Restrict access based on role

### 5. Sales Management
- View sales history
- View sale details
- Filter sales by:
  - Date
  - Cashier
  - Channel
  - Payment Method
  - Refund status
- Export sales to CSV
- Sales summary cards
- Show field or in-store selling channel

### 6. Refund Feature
- Refund completed sales
- Refund reason support
- Automatic stock return on refund
- Refund tracking in sales record
- Restriction: cashier cannot perform refund

### 7. Inventory Monitoring
- Automatic stock deduction after sale
- Stock return during refund
- Inventory movement logs
- Low stock alert system

### 8. CSV Import
- Import products by CSV
- Import categories by CSV
- Import subcategories by CSV
- Automatic unique barcode generation when barcode is missing
- Downloadable CSV templates

### 9. Customer Support
- Optional customer association in sales
- Customer name shown in sale details if available

### 10. Settings
- Store information
- VAT settings
- Cash drawer options
- Other system preferences

## Selling Modes
The system supports two selling channels:
- In Store
- Field

This is useful for businesses that also have agents or staff selling outside the physical store.

## Benefits
- Faster checkout process
- Better stock monitoring
- Cleaner sales tracking
- Controlled staff access
- Easier data import
- More professional store operations

## Target Users
This system is suitable for:
- Liquor stores
- Convenience stores
- Mini-marts
- Small retail businesses with inventory and cashier operations

## Technical Stack
- Pure PHP MVC
- MySQL
- Docker
- HTML / CSS / JavaScript

## Summary
This POS System is a practical and scalable solution for retail businesses that need reliable sales processing, inventory control, product organization, and user permission management in one centralized platform.
