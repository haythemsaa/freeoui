import { useTranslation } from 'react-i18next'
import { Globe } from 'lucide-react'

export const LanguageSwitcher = () => {
  const { i18n } = useTranslation()

  const languages = [
    { code: 'fr', name: 'Français', flag: '🇫🇷' },
    { code: 'ar', name: 'العربية', flag: '🇹🇳' },
  ]

  const changeLanguage = (languageCode: string) => {
    i18n.changeLanguage(languageCode)

    // Update HTML dir and lang attributes
    document.documentElement.dir = languageCode === 'ar' ? 'rtl' : 'ltr'
    document.documentElement.lang = languageCode
  }

  return (
    <div className="relative group">
      <button className="flex items-center gap-2 px-3 py-2 text-sm text-gray-700 hover:bg-gray-100 rounded-md transition-colors">
        <Globe className="w-4 h-4" />
        <span>
          {languages.find(lang => lang.code === i18n.language)?.flag}
        </span>
      </button>

      <div className="absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg hidden group-hover:block z-50 border border-gray-200">
        {languages.map((language) => (
          <button
            key={language.code}
            onClick={() => changeLanguage(language.code)}
            className={`
              w-full flex items-center gap-3 px-4 py-2 text-sm text-left hover:bg-gray-50 transition-colors
              ${i18n.language === language.code ? 'bg-orange-50 text-orange-600 font-medium' : 'text-gray-700'}
            `}
          >
            <span className="text-lg">{language.flag}</span>
            <span>{language.name}</span>
          </button>
        ))}
      </div>
    </div>
  )
}
