import { test, expect } from '@playwright/test'

test.describe('Authentication', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/')
  })

  test('should display login page', async ({ page }) => {
    await expect(page).toHaveTitle(/FreeOui/)
    await expect(page.getByRole('heading', { name: /connexion/i })).toBeVisible()
  })

  test('should show validation errors for empty fields', async ({ page }) => {
    await page.getByRole('button', { name: /connexion/i }).click()

    await expect(page.getByText(/email.*requis/i)).toBeVisible()
    await expect(page.getByText(/mot de passe.*requis/i)).toBeVisible()
  })

  test('should show error for invalid credentials', async ({ page }) => {
    await page.getByLabel(/email/i).fill('wrong@email.com')
    await page.getByLabel(/mot de passe/i).fill('wrongpassword')
    await page.getByRole('button', { name: /connexion/i }).click()

    await expect(page.getByText(/identifiants invalides/i)).toBeVisible()
  })

  test('should login successfully with valid credentials', async ({ page }) => {
    await page.getByLabel(/email/i).fill('merchant@freeoui.tn')
    await page.getByLabel(/mot de passe/i).fill('password123')
    await page.getByRole('button', { name: /connexion/i }).click()

    // Should redirect to dashboard
    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.getByRole('heading', { name: /tableau de bord/i })).toBeVisible()
  })

  test('should logout successfully', async ({ page }) => {
    // Login first
    await page.getByLabel(/email/i).fill('merchant@freeoui.tn')
    await page.getByLabel(/mot de passe/i).fill('password123')
    await page.getByRole('button', { name: /connexion/i }).click()

    await page.waitForURL(/\/dashboard/)

    // Logout
    await page.getByRole('button', { name: /déconnexion/i }).click()

    // Should redirect to login
    await expect(page).toHaveURL(/\/login/)
  })

  test('should persist login after page refresh', async ({ page }) => {
    // Login
    await page.getByLabel(/email/i).fill('merchant@freeoui.tn')
    await page.getByLabel(/mot de passe/i).fill('password123')
    await page.getByRole('button', { name: /connexion/i }).click()

    await page.waitForURL(/\/dashboard/)

    // Refresh page
    await page.reload()

    // Should still be on dashboard
    await expect(page).toHaveURL(/\/dashboard/)
    await expect(page.getByRole('heading', { name: /tableau de bord/i })).toBeVisible()
  })
})
