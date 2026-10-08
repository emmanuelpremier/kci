<?php
include_once 'site_config.php';
?>
<style>
.kc-admin-link:hover,.kc-admin-link:focus-visible,.kc-admin-link:focus{opacity:1 !important;}
@media (max-width:600px){.kc-admin-link{display:flex !important;justify-content:center;margin:.6em auto 0 !important;}}
</style>
<footer class="kc118">
	<div class="kc119">
		<div class="kc120">
			<div class="kc121">
				<div class="kc122"><img src="kci_image/img13.webp"></div>
				<div class="kc123">
					<strong>KINGDOMITE</strong>
					<span>CHURCH INTERNATIONAL</span>
				</div>
			</div>
			<p>
				Building a people who know God, love people
				and live out His purpose.
			</p>
		</div>

		<div class="kc124">
			<h4>Quick Links</h4>
			<a href="index.php">Home</a>
			<a href="aboutus.php">About</a>
			<a href="events.php">Events</a>
			<a href="min.php">Ministries</a>
			<a href="contact.php">Contact</a>
		</div>

		<div class="kc124">
			<h4>Contact</h4>
			<p>Phone / WhatsApp: <a href="<?php echo htmlspecialchars(CHURCH_WHATSAPP_URL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); ?>" target="_blank" rel="noopener"><?php echo htmlspecialchars(CHURCH_PHONE, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); ?></a></p>
			<p>Email: <a href="mailto:<?php echo htmlspecialchars(CHURCH_EMAIL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); ?>"><?php echo htmlspecialchars(CHURCH_EMAIL, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); ?></a></p>
		</div>

		<div class="kc124">
			<h4>Location</h4>
			<p><?php echo htmlspecialchars(CHURCH_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); ?></p>
			<p><?php echo htmlspecialchars(CHURCH_ADDRESS, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); ?></p>
		</div>
	</div>

	<div class="kc125">
		<p>&copy; <?php echo date('Y'); ?> <?php echo htmlspecialchars(CHURCH_NAME, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8', false); ?>. All Rights Reserved.</p>
		<a class="kc-admin-link" href="admin/login.php" rel="nofollow noopener" style="font-size:.8rem;color:inherit;opacity:.6;text-decoration:none;display:inline-flex;align-items:center;gap:.35em;margin-top:.4em;"><i class="fa-solid fa-lock" aria-hidden="true"></i> Admin Login</a>
	</div>
</footer>
<script src="kci_nav.js?v=1" defer></script>
