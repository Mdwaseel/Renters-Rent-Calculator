# Renters Calculator Widget for Elementor

A professional, fully-featured WordPress plugin that adds a customizable rent payment calculator widget to Elementor. Calculate monthly payments with support for multiple banks, interest rates, and installment plan fees.

![Version](https://img.shields.io/badge/version-1.3.1-blue.svg)
![WordPress](https://img.shields.io/badge/wordpress-5.0%2B-blue.svg)
![Elementor](https://img.shields.io/badge/elementor-3.0%2B-pink.svg)
![License](https://img.shields.io/badge/license-GPL--2.0%2B-green.svg)

## 📋 Features

- **🏦 Multi-Bank Support** - Pre-configured with 15+ UAE banks (ADCB, FAB, ENBD, HSBC, DIB, and more)
- **💰 Accurate Interest Calculations** - Monthly recurring interest like traditional loans
- **📊 Payment Schedule** - Detailed breakdown showing principal and interest per month
- **🎨 Fully Customizable** - Control colors, styles, and CTA URLs through Elementor
- **📱 Responsive Design** - Mobile-first design that looks great on all devices
- **♿ Accessible** - ARIA labels and semantic HTML for screen readers
- **⚡ Lightweight** - No external dependencies, pure vanilla JavaScript
- **🔒 Secure** - Follows WordPress and Elementor best practices

## 🎯 Use Cases

Perfect for:
- Real estate websites
- Property rental platforms
- Financial service providers
- Tenant payment processing companies
- Property management firms

## 📸 Screenshots

### Calculator Interface
The widget displays a clean, professional interface with bank selection, amount input, tenure options, and instant calculations.

### Payment Schedule
Expandable payment schedule shows month-by-month breakdown of principal and interest payments.

### Elementor Integration
Seamlessly integrates with Elementor's editor with full style controls.

## 🚀 Installation

### Method 1: Upload via WordPress Admin

1. Download the `renters-calculator-widget.php` file
2. Go to WordPress Admin → Plugins → Add New → Upload Plugin
3. Choose the file and click "Install Now"
4. Activate the plugin
5. Add the widget through Elementor editor

### Method 2: Manual Installation

1. Download the plugin file
2. Upload to `/wp-content/plugins/renters-calculator/` directory
3. Activate through the WordPress Plugins menu
4. Add the widget through Elementor editor

### Method 3: GitHub Installation

```bash
cd wp-content/plugins/
git clone [https://github.com/Mdwaseel/Renters-Rent-Calculator](https://github.com/Mdwaseel/Renters-Rent-Calculator.git)
```

Then activate through WordPress admin.

## 📦 Requirements

- **WordPress:** 5.0 or higher
- **PHP:** 7.0 or higher
- **Elementor:** 3.0 or higher (Free or Pro)

## 🎨 Usage

### Adding the Widget

1. Edit any page with Elementor
2. Search for "Renters Calculator" in the widget panel
3. Drag and drop onto your page
4. Customize settings in the left panel

### Customization Options

#### Style Controls
- **Widget Background** - Main container background color
- **Card Background** - Calculator card background
- **Text Color** - Primary text color
- **Muted Text** - Secondary text color
- **Primary Color** - Buttons and highlights
- **Primary Hover** - Button hover state
- **Border Color** - Card and input borders

#### Content Controls
- **CTA Button URL** - Customize where "Start Your Rent Plan" button redirects
  - Query parameters automatically appended: `amount`, `tenure`, `bank`, `method`
  - Example: `https://yoursite.com/pay?amount=10000&tenure=6&bank=ADCB&method=epp`

### Supported Banks

The widget includes the following UAE banks with their respective fee structures:

| Bank | Min Amount | Interest Rates | Processing Fees |
|------|------------|----------------|-----------------|
| ADCB | AED 500 | 0.7% - 0.65% | - |
| Ajman Bank | AED 1,000 | 1.5% - 0.99% | - |
| Al Hilal | AED 1,000 | 0.33% - 0.25% | - |
| Arab Bank UAE | AED 1,000 | 0.5% | AED 49 |
| CBD | AED 1,000 | 0% | 1.5% - 3% |
| CBI | AED 500 | 1% | AED 35 |
| Deem Finance | AED 1,000 | 0% | 1% - 3% |
| Dubai Islamic | AED 1,000 | 0% | 3% - 4.8% |
| Emirates Islamic | AED 500 | 0% | 2% |
| ENBD | AED 750 | 2.51% - 2.54% | AED 49 |
| FAB | AED 500 | 1.2% - 1.1% | - |
| HSBC | AED 500 | 0.99% - 0.69% | - |
| RAK | AED 1,000 | 0% | AED 149 - 199 |
| SCB | AED 500 | 0% | AED 49 - 98 |

## 💡 How It Works

### Interest Calculation

The calculator uses a **monthly recurring interest model** similar to car or home loans:

```
Monthly Interest = Principal × Monthly Rate × Number of Months
Total Amount = Principal + Total Interest + Processing Fees
Monthly Payment = Total Amount ÷ Number of Months
```

**Example:**
- Principal: AED 10,000
- Bank: ADCB
- Tenure: 6 months
- Interest Rate: 0.7% per month

**Calculation:**
- Total Interest: 10,000 × 0.007 × 6 = AED 420
- Total Amount: 10,000 + 420 = AED 10,420
- Monthly Payment: 10,420 ÷ 6 = **AED 1,736.67**
  - Principal per month: AED 1,666.67
  - Interest per month: AED 70.00

### Processing Fees

Processing fees are **one-time charges** applied at the start:
- Can be percentage-based (e.g., 2% of principal)
- Can be fixed amount (e.g., AED 49)
- Added to total amount and distributed across monthly payments

## 🔧 Customization

### Modifying Bank Data

Bank data is stored in JSON format within the widget. To add or modify banks:

1. Locate the `<script id="eppData">` section in the render method
2. Add/edit bank entries following this structure:

```json
{
  "name": "Bank Name",
  "country": "UAE",
  "min_amount_aed": 500.0,
  "interest_rates": {
    "6": {"type": "percent", "value": 0.007},
    "12": {"type": "percent", "value": 0.007}
  },
  "processing_fees": {
    "6": {"type": "fixed_aed", "value": 49}
  }
}
```

### Styling via CSS

Add custom CSS to override default styles:

```css
/* Custom primary color */
.renters-calc {
  --primary: #FF5722;
  --primary-600: #E64A19;
}

/* Larger monthly amount display */
.renters-calc .monthly {
  font-size: 36px;
}

/* Custom border radius */
.renters-calc .card {
  border-radius: 24px;
}
```

### JavaScript Hooks

The calculator exposes its state and functions within a closure. For advanced customization, you can modify the inline JavaScript in the `rcw_inline_js()` function.

## 🐛 Troubleshooting

### Widget Not Appearing in Elementor

**Solution:** Ensure Elementor is installed and activated. Check WordPress admin for any plugin conflict notices.

### Calculations Seem Incorrect

**Solution:** Verify bank data JSON is valid. Check browser console for JavaScript errors.

### Styling Issues

**Solution:** Clear WordPress and browser cache. Check for theme CSS conflicts using browser DevTools.

### Button Not Working

**Solution:** Verify the CTA URL is properly set in widget settings. Check browser console for errors.

## 🤝 Contributing

Contributions are welcome! Here's how you can help:

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/AmazingFeature`)
3. Commit your changes (`git commit -m 'Add some AmazingFeature'`)
4. Push to the branch (`git push origin feature/AmazingFeature`)
5. Open a Pull Request

### Development Guidelines

- Follow WordPress Coding Standards
- Test with latest WordPress and Elementor versions
- Ensure mobile responsiveness
- Add inline documentation for complex logic
- Test accessibility with screen readers

## 📝 Changelog

### Version 1.3.1 (Current)
- ✅ Fixed interest calculation to apply monthly (like traditional loans)
- ✅ Improved payment schedule display with detailed breakdown
- ✅ Added clear labels for recurring vs one-time fees
- ✅ Enhanced error handling and validation
- ✅ Updated bank data structure for better clarity

### Version 1.3.0
- Added support for both interest rates and processing fees
- Improved responsive design for mobile devices
- Added ARIA labels for better accessibility
- Enhanced error messages and validation

### Version 1.2.0
- Added customizable CTA URL with query parameters
- Improved Elementor style controls
- Added Inter font family
- Enhanced visual design

### Version 1.0.0
- Initial release
- Basic calculator functionality
- Multi-bank support
- Elementor integration

## 📄 License

This plugin is licensed under the GPL v2 or later.

```
This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation; either version 2 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

## 👨‍💻 Author

**Md Waseel**

## 🙏 Acknowledgments

- Built with [Elementor](https://elementor.com/)
- Powered by [WordPress](https://wordpress.org/)
- Font: [Inter](https://fonts.google.com/specimen/Inter) by Rasmus Andersson

## 📞 Support

For support, bug reports, or feature requests:
- Open an issue on [GitHub](https://github.com/Mdwaseel/Renters-Rent-Calculator/issues)
- Contact: [mdwaseel2311@gmail.com]

**⭐ If you find this plugin helpful, please consider giving it a star on GitHub!**
