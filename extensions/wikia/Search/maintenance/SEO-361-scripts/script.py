from selenium import webdriver
from time import sleep

fx = webdriver.phantomjs.webdriver.WebDriver()
fx.implicitly_wait(10)
#fx.maximize()
fx.get('http://10.8.66.43/t/SEO/views/CrawlerTool_0/D-BOT?:embed=y&:showShareOptions=true&:display_count=no&:showVizHome=no')

fx.find_element_by_css_selector('input[type=text]').send_keys('saipetchk')
fx.find_element_by_css_selector('input[type=password]').send_keys('uaelbat2015')
fx.find_element_by_css_selector('button').click()

fx.find_element_by_css_selector('canvas')
# Wait for spinner to stop spinning
sleep(5)

print '<img src="data:image/gif;base64,' + fx.get_screenshot_as_base64() + '">'

fx.quit()

