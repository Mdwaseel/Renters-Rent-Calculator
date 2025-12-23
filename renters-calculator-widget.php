<?php
/**
 * Plugin Name: Renters Calculator Widget
 * Description: A custom Elementor widget for a responsive rent payment calculator with style controls and configurable CTA URL.
 * Version: 1.3.1
 * Author: Md Waseel
 * Text Domain: renters-calculator
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/* -------------------------------------------------------------------------
 * 1) Guard on activation (prevent crash if Elementor missing)
 * ------------------------------------------------------------------------- */
register_activation_hook( __FILE__, function () {
    if ( ! did_action( 'elementor/loaded' ) && ! class_exists( '\Elementor\Plugin' ) ) {
        deactivate_plugins( plugin_basename( __FILE__ ) );
        wp_die(
            esc_html__( 'Renters Calculator Widget requires Elementor to be active. Activate Elementor and try again.', 'renters-calculator' ),
            esc_html__( 'Plugin dependency missing', 'renters-calculator' ),
            [ 'back_link' => true ]
        );
    }
} );

/* -------------------------------------------------------------------------
 * 2) Check if Elementor is loaded before doing anything else
 * ------------------------------------------------------------------------- */
add_action( 'plugins_loaded', function() {
    // Check if Elementor is loaded
    if ( ! did_action( 'elementor/loaded' ) ) {
        add_action( 'admin_notices', function() {
            echo '<div class="notice notice-warning is-dismissible"><p>';
            echo esc_html__( 'Renters Calculator Widget requires Elementor to be installed and activated.', 'renters-calculator' );
            echo '</p></div>';
        } );
        return;
    }
    
    // Initialize plugin after Elementor is loaded
    add_action( 'init', 'rcw_init_plugin' );
} );

function rcw_init_plugin() {
    // Register empty asset handles early
    wp_register_style( 'renters-calculator-style', false, [], '1.3.1' );
    wp_register_script( 'renters-calculator-script', false, [], '1.3.1', true );
    
    // Enqueue Inter font
    wp_register_style( 'renters-calculator-fonts', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', [], '1.3.1' );
}

/* Inline CSS for the widget */
function rcw_inline_css() {
    $css = <<<CSS
.renters-calc{
  --primary:#0052CC; --primary-600:#003D99; --bg:#fff; --surface:#F7F9FC;
  --text:#111827; --muted:#6B7280; --border:#E5E7EB; --chip:#EEF2FF;
  --shadow:0 8px 24px rgba(16,24,40,.08); --radius-xl:16px; --radius-lg:12px; --radius-md:10px;
  background:var(--bg);
  font-family:'Inter', system-ui, -apple-system, Segoe UI, Roboto, Arial, sans-serif;
  border-radius: 20px;
}
.renters-calc *{ box-sizing:border-box }
.renters-calc .card{ background:var(--surface); border:1px solid var(--border); border-radius:var(--radius-xl); box-shadow:var(--shadow) }
.renters-calc .card-inner{ padding:18px }
.renters-calc .calc h3{ margin:0 0 12px; font-size:16px; font-weight:700; color:var(--text) }
.renters-calc .calc-row{ display:grid; grid-template-columns:1fr; gap:10px; margin-bottom:12px }
.renters-calc .label{ font-size:12px; letter-spacing:.12em; text-transform:uppercase; color:var(--muted); font-weight:700 }
.renters-calc .field{ background:#fff; border:1px solid var(--border); border-radius:12px; padding:12px; display:flex; align-items:center; gap:10px }
.renters-calc .field input, .renters-calc .field select{ flex:1; background:transparent; border:none; outline:none; color:var(--text); font-size:16px }
.renters-calc .field .prefix{ color:var(--primary); font-weight:600 }
.renters-calc .tenures{ display:flex; flex-wrap:wrap; gap:8px }
.renters-calc .tenure-btn{ border:1px solid var(--border); background:#fff; color:var(--text); padding:10px 12px; border-radius:12px; font-weight:600; cursor:pointer }
.renters-calc .tenure-btn[aria-pressed="true"]{ background:var(--primary); color:#fff; border-color:var(--primary) }
.renters-calc .tenure-btn[disabled]{ opacity:.4; cursor:not-allowed }
.renters-calc .calc-result{ background:#fff; border:1px solid var(--border); border-radius:12px; padding:14px; display:flex; align-items:flex-start; justify-content:space-between; gap:12px; color:var(--text) }
.renters-calc .monthly{ font-size:clamp(22px,4vw,30px); font-weight:800; color:var(--primary-600) }
.renters-calc .fee-section{ margin-top:8px; padding-top:8px; border-top:1px solid var(--border) }
.renters-calc .fee-section:first-child{ margin-top:8px; padding-top:8px; border-top:none; border-top: 1px solid var(--border); }
.renters-calc .fee-type-label{ font-size:12px; letter-spacing:.12em; text-transform:uppercase; color:var(--primary); font-weight:700; margin-bottom:4px }
.renters-calc .fineprint{ color:var(--muted); font-size:12px }
.renters-calc .meta{ font-size:13px; color:var(--muted); margin-top:6px }
.renters-calc .primary{ background:var(--primary); color:#fff; border:none; padding:14px 18px; border-radius:12px; font-weight:700; cursor:pointer }
.renters-calc .primary:hover{ background:var(--primary-600) }
.renters-calc .error{ color:#b91c1c; font-size:12px; margin-top:6px }
.renters-calc .small{ font-size:14px }
.renters-calc .muted{ color:var(--muted) }
@media (max-width:600px){
  .renters-calc .card-inner{ padding:12px }
  .renters-calc .calc-row{ gap:8px; margin-bottom:10px }
  .renters-calc .field{ padding:10px }
  .renters-calc .calc-result{ flex-direction:column; align-items:stretch }
  .renters-calc .calc-result>div:last-child{ justify-content:flex-end }
  .renters-calc .monthly{ font-size:clamp(20px,3.5vw,26px) }
  .renters-calc .primary{ width:100%; text-align:center }
}
CSS;
    wp_add_inline_style( 'renters-calculator-style', $css );
}

/* Inline JS for the widget */
function rcw_inline_js() {
    $js = <<<'JS'
(function(){
  // Check if we're in Elementor editor mode
  if (typeof elementorFrontend !== "undefined" && elementorFrontend.isEditMode()) { 
    return; 
  }
  
  // Utility functions
  const fmtAED = (n) => {
    try {
      return new Intl.NumberFormat("en-AE", {style: "currency", currency: "AED"}).format(n);
    } catch(e) {
      return "AED " + n.toFixed(2);
    }
  };
  
  const byId = (id) => document.getElementById(id);
  
  // Get DOM elements
  const amountEl = byId("amount");
  const monthlyEl = byId("monthly");
  const fineprintEl = byId("fineprint");
  const bankFeeEl = byId("bankFee");
  const scheduleEl = byId("schedule");
  const bankSelect = byId("bank");
  const tenuresWrap = byId("tenures");
  const startPaymentBtn = byId("startPayment");
  const minError = byId("minError");
  const wrapper = document.querySelector(".renters-calc");
  
  // Exit if required elements don't exist
  if (!wrapper || !amountEl || !bankSelect || !startPaymentBtn) {
    console.warn("Required elements not found for renters calculator");
    return;
  }
  
  const ctaUrl = wrapper.dataset.ctaUrl || "https://www.getrenters.io/pay";
  let EPP_DATA = {banks: []};
  
  // Load EPP data
  async function loadEppData() { 
    try { 
      const eppDataEl = document.getElementById("eppData");
      if (eppDataEl && eppDataEl.textContent) {
        EPP_DATA = JSON.parse(eppDataEl.textContent);
      }
    } catch(e) { 
      console.warn("EPP data JSON parse error", e);
      EPP_DATA = {banks: []};
    } 
  }
  
  // Application state
  const state = { 
    bank: null, 
    amount: 10000, 
    months: null
  };
  
  function setAmount(v) { 
    const n = Number(v); 
    state.amount = (isFinite(n) && n > 0) ? n : 0; 
    update(); 
  }
  
  function setBank(name) { 
    state.bank = (EPP_DATA.banks || []).find(b => b && b.name === name) || null; 
    renderTenures(); 
    const avail = availableMonths(); 
    state.months = avail.length ? avail[0] : null; 
    update(); 
  }
  
  function availableMonths() { 
    if (!state.bank) return [];
    
    // Combine months from both interest rates and Installment Plan Fees
    const interestMonths = state.bank.interest_rates ? Object.keys(state.bank.interest_rates) : [];
    const processingMonths = state.bank.processing_fees ? Object.keys(state.bank.processing_fees) : [];
    
    const allMonths = [...new Set([...interestMonths, ...processingMonths])]
      .map(x => parseInt(x, 10))
      .filter(x => !isNaN(x) && x > 0)
      .sort((a, b) => a - b);
    
    return allMonths;
  }
  
  function renderTenures() {
    if (!tenuresWrap) return;
    tenuresWrap.innerHTML = "";
    
    const months = availableMonths();
    if (months.length === 0) {
      tenuresWrap.innerHTML = '<span class="muted small">No plans available for this bank</span>';
      return;
    }
    
    months.forEach(m => {
      const btn = document.createElement("button");
      btn.className = "tenure-btn"; 
      btn.textContent = `${m} mo`; 
      btn.setAttribute("data-months", m);
      btn.setAttribute("aria-pressed", String(state.months === m));
      btn.addEventListener("click", () => { 
        state.months = m; 
        update(); 
        setPressed(m); 
      });
      tenuresWrap.appendChild(btn);
    });
    
    if (months.length > 0 && !state.months) {
      state.months = months[0];
    }
    
    setPressed(state.months);
  }
  
  function setPressed(m) { 
    if (!tenuresWrap) return; 
    tenuresWrap.querySelectorAll(".tenure-btn").forEach(btn => {
      const btnMonths = parseInt(btn.dataset.months, 10);
      btn.setAttribute("aria-pressed", String(btnMonths === m));
    });
  }
  
  function getCurrentFees() {
    if (!state.bank || !state.months) return { interest: null, processing: null };
    
    const interestFee = state.bank.interest_rates ? 
      state.bank.interest_rates[String(state.months)] : null;
    const processingFee = state.bank.processing_fees ? 
      state.bank.processing_fees[String(state.months)] : null;
    
    return { interest: interestFee, processing: processingFee };
  }
  
  function computeFees() {
    const amt = state.amount;
    const mo = state.months || 0; // FIX: Define mo variable here
    const { interest, processing } = getCurrentFees();
    
    let interestAED = 0, interestLabel = "Not available";
    let processingAED = 0, processingLabel = "Not available";
    
    // Calculate interest (monthly recurring)
    if (interest) {
      if (interest.type === "percent") { 
  const monthlyRate = Number(interest.value || 0);
  interestAED = amt * monthlyRate * mo;
  const monthlyPercentage = (monthlyRate * 100).toFixed(2);
  interestLabel = `${monthlyPercentage}% per month`;
      } else if (interest.type === "fixed_aed") { 
  interestAED = Number(interest.value || 0) * mo;
  interestLabel = `${fmtAED(Number(interest.value || 0))} per month`;
}
    }
    
    // Calculate processing fees (one-time)
    if (processing) {
      if (processing.type === "percent") { 
        processingAED = amt * Number(processing.value || 0); 
        processingLabel = `${(Number(processing.value || 0) * 100).toFixed(2)}% (${fmtAED(processingAED)})`;
      } else if (processing.type === "fixed_aed") { 
        processingAED = Number(processing.value || 0); 
        processingLabel = fmtAED(processingAED);
      }
    }
    
    return { interestAED, interestLabel, processingAED, processingLabel };
  }
  
  function compute() {
    const amt = state.amount;
    const mo = state.months || 0;
    
    const { interestAED, interestLabel, processingAED, processingLabel } = computeFees();
    
    // Monthly payment includes: (principal + total interest + processing fees) / months
    const totalAmount = amt + interestAED + processingAED;
    const monthly = mo ? (totalAmount / mo) : 0;
    
    return { monthly, interestAED, interestLabel, processingAED, processingLabel };
  }
  
  function renderSchedule() {
    if (!scheduleEl) return;
   
    const m = state.months;
    const amt = state.amount;
   
    if (!m || !amt) {
        scheduleEl.innerHTML = '<span class="muted">Enter amount to see schedule.</span>';
        return;
    }
   
    const { monthly, interestAED, processingAED } = compute();
    const principalPerMonth = amt / m;
    const interestPerMonth = interestAED / m;
    
    let rows = "";
   
    for (let i = 1; i <= m; i++) {
    rows += `<tr>
      <td>Month ${i}</td>
      <td>${fmtAED(monthly)}</td>
    </tr>`;
}
   
    scheduleEl.innerHTML = `
      <div style="overflow:auto;">
        <table style="width:100%; border-collapse: collapse;">
          <thead><tr>
            <th style="text-align:left; padding:6px; border-bottom:1px solid var(--border);">Period</th>
            <th style="text-align:left; padding:6px; border-bottom:1px solid var(--border);">Breakdown</th>
          </tr></thead>
          <tbody>${rows}</tbody>
          <tfoot>
            <tr><td style="padding:6px; border-top:1px solid var(--border); font-weight:700;">Total principal</td><td style="padding:6px; border-top:1px solid var(--border); font-weight:700;">${fmtAED(amt)}</td></tr>
            <tr><td style="padding:6px;">Total interest (${m} months)</td><td style="padding:6px;">${fmtAED(interestAED)}</td></tr>
            <tr><td style="padding:6px;">Installment Plan fees (one-time)</td><td style="padding:6px;">${fmtAED(processingAED)}</td></tr>
            <tr><td style="padding:6px; font-weight:700;">Grand total</td><td style="padding:6px; font-weight:700;">${fmtAED(amt + interestAED + processingAED)}</td></tr>
          </tfoot>
        </table>
      </div>`;
  }
  
  function update() {
    if (!monthlyEl || !bankFeeEl || !fineprintEl || !minError || !startPaymentBtn) return;
    
    const amt = state.amount;
    const mo = state.months;
    const bank = state.bank;
    const min = (bank && bank.min_amount_aed) ? bank.min_amount_aed : 0;
    
    // Check minimum amount
    if (amt && min && amt < min) { 
      minError.style.display = "block"; 
      minError.textContent = `Minimum for ${bank.name} is ${fmtAED(min)}.`; 
      startPaymentBtn.disabled = true; 
    }
    else { 
      minError.style.display = "none"; 
      minError.textContent = ""; 
      startPaymentBtn.disabled = false; 
    }
    
    const { monthly, interestLabel, processingLabel } = compute();
    
    monthlyEl.textContent = (amt && mo) ? fmtAED(monthly) : "—";
    
    // Display both fee types
    if (amt && mo && bank) {
      bankFeeEl.innerHTML = `
        <div class="fee-section">
          <div class="fee-type-label">Interest Rate (Monthly Recurring)</div>
          <div class="meta">${interestLabel}</div>
        </div>
        <div class="fee-section">
          <div class="fee-type-label">Installment Plan Fees (One-Time)</div>
          <div class="meta">${processingLabel}</div>
        </div>
      `;
    } else {
      bankFeeEl.textContent = "—";
    }
    
    fineprintEl.textContent = (amt && mo && bank) ? 
      `Based on ${fmtAED(amt)} over ${mo} month${mo > 1 ? "s" : ""} with ${bank.name}. Your bank shows exact terms before you confirm.` : 
      "Enter amount and choose a bank to see your estimate.";
    
    renderSchedule();
  }
  
  function goToCheckout() {
    if (!state.bank || !state.months || !state.amount) return;
    
    // Parse the base URL and existing parameters
    const urlParts = ctaUrl.split('?');
    const baseUrl = urlParts[0];
    const existingParams = urlParts[1] || '';
    
    // Build new parameters
    const newParams = `amount=${encodeURIComponent(state.amount)}&tenure=${encodeURIComponent(state.months)}&bank=${encodeURIComponent(state.bank.name)}&method=epp`;
    
    // Combine parameters
    const url = `${baseUrl}?${existingParams}${existingParams ? '&' : ''}${newParams}`;
    
    window.location.href = url;
  }
  
  function init() {
    try {
      const banks = (EPP_DATA && EPP_DATA.banks ? EPP_DATA.banks : [])
        .filter(b => b && (
          (b.interest_rates && Object.keys(b.interest_rates).length > 0) ||
          (b.processing_fees && Object.keys(b.processing_fees).length > 0)
        ));
      
      // Clear existing options
      bankSelect.innerHTML = '<option value="">Select a bank</option>';
      
      // Add bank options
      banks.forEach(b => { 
        const opt = document.createElement("option"); 
        opt.value = b.name; 
        opt.textContent = b.name; 
        bankSelect.appendChild(opt); 
      });
      
      // Set default bank if available
      if (banks.length) { 
        setBank(banks[0].name); 
        bankSelect.value = banks[0].name;
      }
      
      // Set initial amount
      amountEl.value = state.amount;
      
      // Add event listeners
      amountEl.addEventListener("input", e => setAmount(e.target.value));
      bankSelect.addEventListener("change", e => setBank(e.target.value));
      startPaymentBtn.addEventListener("click", goToCheckout);
      
      // Initial update
      update();
    } catch (error) {
      console.error("Error initializing renters calculator:", error);
    }
  }
  
  // Initialize when ready
  if (document.readyState === "loading") { 
    document.addEventListener("DOMContentLoaded", async () => { 
      await loadEppData(); 
      init(); 
    }); 
  } else { 
    loadEppData().then(init).catch(error => {
      console.error("Error loading EPP data:", error);
      init(); // Try to initialize anyway
    });
  }
})();
JS;
    wp_add_inline_script( 'renters-calculator-script', $js );
}

/* Attach inline code after Elementor registers assets */
add_action( 'elementor/frontend/after_register_styles', 'rcw_inline_css' );
add_action( 'elementor/frontend/after_register_scripts', 'rcw_inline_js' );

/* Enqueue styles and scripts on frontend */
add_action( 'wp_enqueue_scripts', function () {
    if ( did_action( 'elementor/loaded' ) ) {
        wp_enqueue_style( 'renters-calculator-style' );
        wp_enqueue_script( 'renters-calculator-script' );
        wp_enqueue_style( 'renters-calculator-fonts' );
    }
} );

/* -------------------------------------------------------------------------
 * 3) Register the widget AFTER Elementor loads
 * ------------------------------------------------------------------------- */
add_action( 'elementor/widgets/register', function( $widgets_manager ) {

    // Double-check Elementor classes exist
    if ( ! class_exists( '\Elementor\Widget_Base' ) || ! class_exists( '\Elementor\Controls_Manager' ) ) {
        return;
    }

    class Renters_Calculator_Widget extends \Elementor\Widget_Base {
        
        public function get_name() { 
            return 'renters_calculator'; 
        }
        
        public function get_title() { 
            return esc_html__( 'Renters Calculator', 'renters-calculator' ); 
        }
        
        public function get_icon() { 
            return 'eicon-calculator'; 
        }
        
        public function get_categories() { 
            return [ 'general' ]; 
        }
        
        public function get_keywords() { 
            return [ 'rent', 'calculator', 'payment', 'epp' ]; 
        }

        public function get_script_depends() { 
            return [ 'renters-calculator-script' ]; 
        }
        
        public function get_style_depends() { 
            return [ 'renters-calculator-style', 'renters-calculator-fonts' ]; 
        }

        protected function register_controls() {
            // Style controls section
            $this->start_controls_section( 'style_section', [
                'label' => esc_html__( 'Style', 'renters-calculator' ),
                'tab'   => \Elementor\Controls_Manager::TAB_STYLE,
            ] );
            
            $this->add_control( 'bg_color', [
                'label' => esc_html__( 'Widget Background', 'renters-calculator' ),
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#ffffff',
                'selectors' => [ '{{WRAPPER}} .renters-calc' => '--bg: {{VALUE}};' ],
            ] );
            
            $this->add_control( 'surface_color', [
                'label' => esc_html__( 'Card Background', 'renters-calculator' ),
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#F7F9FC',
                'selectors' => [ '{{WRAPPER}} .renters-calc' => '--surface: {{VALUE}};' ],
            ] );
            
            $this->add_control( 'text_color', [
                'label' => esc_html__( 'Text', 'renters-calculator' ),
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#111827',
                'selectors' => [ '{{WRAPPER}} .renters-calc' => '--text: {{VALUE}};' ],
            ] );
            
            $this->add_control( 'muted_color', [
                'label' => esc_html__( 'Muted Text', 'renters-calculator' ),
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#6B7280',
                'selectors' => [ '{{WRAPPER}} .renters-calc' => '--muted: {{VALUE}};' ],
            ] );
            
            $this->add_control( 'primary_color', [
                'label' => esc_html__( 'Primary (Buttons/Highlights)', 'renters-calculator' ),
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#0052CC',
                'selectors' => [ '{{WRAPPER}} .renters-calc' => '--primary: {{VALUE}};' ],
            ] );
            
            $this->add_control( 'primary600_color', [
                'label' => esc_html__( 'Primary Hover', 'renters-calculator' ),
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#003D99',
                'selectors' => [ '{{WRAPPER}} .renters-calc' => '--primary-600: {{VALUE}};' ],
            ] );
            
            $this->add_control( 'border_color', [
                'label' => esc_html__( 'Border', 'renters-calculator' ),
                'type'  => \Elementor\Controls_Manager::COLOR,
                'default' => '#E5E7EB',
                'selectors' => [ '{{WRAPPER}} .renters-calc' => '--border: {{VALUE}};' ],
            ] );
            
            $this->end_controls_section();

            // Content section
            $this->start_controls_section( 'content_section', [
                'label' => esc_html__( 'Content', 'renters-calculator' ),
                'tab'   => \Elementor\Controls_Manager::TAB_CONTENT,
            ] );
            
            $this->add_control( 'cta_url', [
                'label' => esc_html__( 'CTA Button URL', 'renters-calculator' ),
                'type'  => \Elementor\Controls_Manager::URL,
                'placeholder' => 'https://www.getrenters.io/pay',
                'default' => [ 
                    'url' => 'https://www.getrenters.io/pay', 
                    'is_external' => false, 
                    'nofollow' => false 
                ],
                'description' => esc_html__( 'URL for "Start Your Rent Plan". Query params (amount, tenure, bank, method) will be appended.', 'renters-calculator' ),
            ] );
            
            $this->end_controls_section();
        }

        protected function render() {
            $settings = $this->get_settings_for_display();
            $cta_url  = ! empty( $settings['cta_url']['url'] ) ? esc_url( $settings['cta_url']['url'] ) : 'https://www.getrenters.io/pay';
            ?>
            <div class="renters-calc" data-cta-url="<?php echo esc_attr( $cta_url ); ?>">
                <div class="card">
                    <div class="card-inner calc" aria-labelledby="calc-title">
                        <h3 id="calc-title"><?php esc_html_e( 'Calculator', 'renters-calculator' ); ?></h3>

                        <div class="calc-row">
                            <div class="label"><?php esc_html_e( 'Bank', 'renters-calculator' ); ?></div>
                            <label class="field" for="bank">
                                <select id="bank" name="bank" aria-describedby="bankHelp">
                                    <option value=""><?php esc_html_e( 'Select a bank', 'renters-calculator' ); ?></option>
                                </select>
                            </label>
                            <div id="bankHelp" class="small muted"><?php esc_html_e( "We'll tailor plans and fees based on your bank's EPP.", 'renters-calculator' ); ?></div>
                        </div>

                        <div class="calc-row">
                            <div class="label"><?php esc_html_e( 'Rent amount', 'renters-calculator' ); ?></div>
                            <label class="field" for="amount">
                                <span class="prefix">AED</span>
                                <input id="amount" name="amount" type="number" inputmode="decimal" min="500" step="50" placeholder="<?php esc_attr_e( 'Enter rent amount (e.g., 10000)', 'renters-calculator' ); ?>" aria-describedby="amtHelp" />
                            </label>
                            <div id="amtHelp" class="small muted"><?php esc_html_e( 'Minimums may apply per bank.', 'renters-calculator' ); ?></div>
                        </div>

                        <div class="calc-row">
                            <div class="label"><?php esc_html_e( 'Tenure', 'renters-calculator' ); ?></div>
                            <div class="tenures" id="tenures" role="tablist" aria-label="<?php esc_attr_e( 'Select installment months', 'renters-calculator' ); ?>"></div>
                        </div>

                        <div class="calc-result" aria-live="polite">
                            <div>
                                <div class="muted small"><?php esc_html_e( 'Estimated monthly', 'renters-calculator' ); ?></div>
                                <div class="monthly" id="monthly">—</div>
                                <div class="meta" id="bankFee">—</div>
                                <div class="fineprint" id="fineprint"><?php esc_html_e( 'Enter amount and choose a bank to see your estimate.', 'renters-calculator' ); ?></div>
                                <div class="error" id="minError" style="display:none;"></div>
                            </div>
                            <div style="display:flex; gap:10px; flex-wrap:wrap;">
                                <button class="primary" id="startPayment"><?php esc_html_e( 'Start Your Rent Plan', 'renters-calculator' ); ?></button>
                            </div>
                        </div>

                        <details style="margin-top:12px;">
                            <summary class="muted"><?php esc_html_e( 'View payment schedule', 'renters-calculator' ); ?></summary>
                            <div id="schedule" class="small" style="margin-top:10px;"></div>
                        </details>
                    </div>
                </div>

                <!-- Embedded bank data with both interest rates and Installment Plan Fees -->
                <script id="eppData" type="application/json">
                {
                    "updated_from_sheet": "Complete fee structure with interest rates and installment Plan Fees",
                    "banks": [
                        {
                            "name": "ADCB",
                            "country": "UAE",
                            "min_amount_aed": 500.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "6": {"type": "percent", "value": 0.007},
                                "9": {"type": "percent", "value": 0.007},
                                "12": {"type": "percent", "value": 0.007},
                                "24": {"type": "percent", "value": 0.0065},
                                "36": {"type": "percent", "value": 0.0065}
                            },
                            "processing_fees": {}
                        },
                        {
                            "name": "Ajman Bank",
                            "country": "UAE",
                            "min_amount_aed": 1000.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.015},
                                "6": {"type": "percent", "value": 0.015},
                                "9": {"type": "percent", "value": 0.0125},
                                "12": {"type": "percent", "value": 0.0125},
                                "18": {"type": "percent", "value": 0.0099},
                                "24": {"type": "percent", "value": 0.0099},
                                "36": {"type": "percent", "value": 0.0099}
                            },
                            "processing_fees": {}
                        },
                        {
                            "name": "Al Hilal",
                            "country": "UAE",
                            "min_amount_aed": 1000.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "6": {"type": "percent", "value": 0.0033},
                                "9": {"type": "percent", "value": 0.0033},
                                "12": {"type": "percent", "value": 0.0025}
                            },
                            "processing_fees": {}
                        },
                        {
                            "name": "Arab Bank UAE",
                            "country": "UAE",
                            "min_amount_aed": 1000.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": "Cardholder",
                            "interest_rates": {
                                "6": {"type": "percent", "value": 0.005},
                                "9": {"type": "percent", "value": 0.005},
                                "12": {"type": "percent", "value": 0.005},
                                "18": {"type": "percent", "value": 0.005}
                            },
                            "processing_fees": {
                                "6": {"type": "fixed_aed", "value": 49},
                                "9": {"type": "fixed_aed", "value": 49},
                                "12": {"type": "fixed_aed", "value": 49},
                                "24": {"type": "fixed_aed", "value": 49}
                            }
                        },
                        {
                            "name": "CBD",
                            "country": "UAE",
                            "min_amount_aed": 1000.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": "Cardholder",
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.0},
                                "6": {"type": "percent", "value": 0.0},
                                "12": {"type": "percent", "value": 0.0}
                            },
                            "processing_fees": {
                                "3": {"type": "percent", "value": 0.015},
                                "6": {"type": "percent", "value": 0.025},
                                "12": {"type": "percent", "value": 0.03}
                            }
                        },
                        {
                            "name": "CBI",
                            "country": "UAE",
                            "min_amount_aed": 500.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "6": {"type": "percent", "value": 0.01},
                                "9": {"type": "percent", "value": 0.01},
                                "12": {"type": "percent", "value": 0.01}
                            },
                            "processing_fees": {
                                "6": {"type": "fixed_aed", "value": 35},
                                "9": {"type": "fixed_aed", "value": 35},
                                "12": {"type": "fixed_aed", "value": 35}
                            }
                        },
                        {
                            "name": "Deem Finance LLC",
                            "country": "UAE",
                            "min_amount_aed": 1000.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": "Cardholder",
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.0},
                                "6": {"type": "percent", "value": 0.0},
                                "12": {"type": "percent", "value": 0.0}
                            },
                            "processing_fees": {
                                "3": {"type": "percent", "value": 0.01},
                                "6": {"type": "percent", "value": 0.02},
                                "12": {"type": "percent", "value": 0.03}
                            }
                        },
                        {
                            "name": "Dubai Islamic Bank",
                            "country": "UAE",
                            "min_amount_aed": 1000.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "6": {"type": "percent", "value": 0.0},
                                "12": {"type": "percent", "value": 0.0}
                            },
                            "processing_fees": {
                                "6": {"type": "percent", "value": 0.03},
                                "12": {"type": "percent", "value": 0.048}
                            }
                        },
                        {
                            "name": "Emirates Islamic",
                            "country": "UAE",
                            "min_amount_aed": 500.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.0},
                                "6": {"type": "percent", "value": 0.0}
                            },
                            "processing_fees": {
                                "3": {"type": "percent", "value": 0.02},
                                "6": {"type": "percent", "value": 0.02}
                            }
                        },
                        {
                            "name": "ENBD",
                            "country": "UAE",
                            "min_amount_aed": 750.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.0251},
                                "6": {"type": "percent", "value": 0.0263},
                                "9": {"type": "percent", "value": 0.0261},
                                "12": {"type": "percent", "value": 0.0254}
                            },
                            "processing_fees": {
                                "6": {"type": "fixed_aed", "value": 49},
                                "12": {"type": "fixed_aed", "value": 49},
                                "24": {"type": "fixed_aed", "value": 49},
                                "36": {"type": "fixed_aed", "value": 49}
                            }
                        },
                        {
                            "name": "FAB",
                            "country": "UAE",
                            "min_amount_aed": 500.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.012},
                                "6": {"type": "percent", "value": 0.012},
                                "9": {"type": "percent", "value": 0.012},
                                "12": {"type": "percent", "value": 0.012},
                                "18": {"type": "percent", "value": 0.012},
                                "24": {"type": "percent", "value": 0.011},
                                "36": {"type": "percent", "value": 0.011}
                            },
                            "processing_fees": {}
                        },
                        {
                            "name": "HSBC",
                            "country": "UAE",
                            "min_amount_aed": 500.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.0099},
                                "6": {"type": "percent", "value": 0.0099},
                                "9": {"type": "percent", "value": 0.0079},
                                "12": {"type": "percent", "value": 0.0069},
                                "18": {"type": "percent", "value": 0.0069}
                            },
                            "processing_fees": {}
                        },
                        {
                            "name": "Mawarid Finance",
                            "country": "UAE",
                            "min_amount_aed": 1000.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": null,
                            "paid_by_second": null,
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.0}
                            },
                            "processing_fees": {}
                        },
                        {
                            "name": "RAK",
                            "country": "UAE",
                            "min_amount_aed": 1000.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": "Cardholder",
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.0},
                                "6": {"type": "percent", "value": 0.0},
                                "12": {"type": "percent", "value": 0.0}
                            },
                            "processing_fees": {
                                "3": {"type": "fixed_aed", "value": 149},
                                "6": {"type": "fixed_aed", "value": 149},
                                "12": {"type": "fixed_aed", "value": 199}
                            }
                        },
                        {
                            "name": "Standard Chartered Bank (SCB)",
                            "country": "UAE",
                            "min_amount_aed": 500.0,
                            "limit_basis": "Cardholder limit",
                            "paid_by_first": "Cardholder",
                            "paid_by_second": null,
                            "interest_rates": {
                                "3": {"type": "percent", "value": 0.0},
                                "6": {"type": "percent", "value": 0.0},
                                "9": {"type": "percent", "value": 0.0}
                            },
                            "processing_fees": {
                                "3": {"type": "fixed_aed", "value": 49},
                                "6": {"type": "fixed_aed", "value": 49},
                                "12": {"type": "fixed_aed", "value": 98}
                            }
                        }
                    ]
                }
                </script>
            </div>
            <?php
        }

        protected function content_template() {
            ?>
            <# var cta_url = settings.cta_url && settings.cta_url.url ? settings.cta_url.url : 'https://www.getrenters.io/pay'; #>
            <div class="renters-calc" data-cta-url="{{{ cta_url }}}">
                <div class="card"><div class="card-inner calc">
                    <h3><?php esc_html_e( 'Calculator', 'renters-calculator' ); ?></h3>
                    <p><?php esc_html_e( 'Choose fee type and enter details to see rent plan estimates.', 'renters-calculator' ); ?></p>
                    <button class="primary"><?php esc_html_e( 'Start Your Rent Plan', 'renters-calculator' ); ?></button>
                </div></div>
            </div>
            <?php
        }
    }

    // Register the widget with error handling
    try {
        $widgets_manager->register( new Renters_Calculator_Widget() );
    } catch ( Exception $e ) {
        // Log error but don't crash
        error_log( 'Renters Calculator Widget registration failed: ' . $e->getMessage() );
    }
} );