# JoomGallery

Development repository of the JoomGallery component for Joomla! 4.

**Project-Website:**
https://www.joomgalleryfriends.net/

**Support-Forum:**
https://www.forum.joomgalleryfriends.net

**Project-Management:**
https://www.forum.joomgalleryfriends.net/forum/index.php?thread/361-priorisierung/&postID=2056#post2056 (ToDo List)

**Project Presentation:**
https://docs.google.com/presentation/d/1kXGfGRrHswU0M3yh0zUvOwW1fYqB07cksHzNxzcEyxI

## Want to contribute?

JoomGallery is an OpenSource project and is developed by users for users. So if you are using JoomGallery feel free to contribute to the project...


[![](https://www.paypalobjects.com/en_US/i/btn/btn_donateCC_LG.gif)](https://www.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=2TBYDQ88VH4PW)

## Literature
### Joomla 4 extension development docs
- [Astrids Blog on Joomla 4 Extension development](https://blog.astrid-guenther.de/en/der-weg-zu-joomla4-erweiterungen/)
- [Nicholas book on Joomla 4 development](https://www.dionysopoulos.me/book.html)
- [Robbies video series on Joomla 4 component development](https://www.youtube.com/playlist?list=PLzio09PZm6TuXGnu-ptpVb90Szkawy9IV)
- [Official Joomla 4 component development docs](https://docs.joomla.org/J4.x:Developing_an_MVC_Component/Introduction)
- [Mattermost channel for Joomla developers](https://joomlacommunity.cloud.mattermost.com/main/channels/extension-development-room)

## Contribute code
### Codestyle guide
PHP: [Codestyle guide for PHP](docs/Codestyleguide.md)

### Setup development environment
https://docs.joomla.org/Setting_up_your_workstation_for_Joomla_development

**Webserver recommendation:**
- https://wampserver.aviatechno.net/ (Windows only)
- https://www.apachefriends.org/index.html (Windows, Linux and macOS)

**IDE/Editor recommendation:**
- https://www.jetbrains.com/phpstorm/ (Windows, Linux and macOS)
- https://code.visualstudio.com/ (Windows, Linux and macOS)

**Git-Client recommendation:**
- https://desktop.github.com/ (Windows and macOS)

**Recommendet approach for proper versioning with Git:**
1. Checkout the repo into a folder of your choice
2. Download the source code of the dev-branch as zip file and install it on Joomla
3. Remove the installed component folders within your Joomla installation
   - administrator/components/com_joomgallery
   - components/com_joomgallery
   - media/com_joomgallery
   - plugins/finder/joomgallerycategories
   - plugins/finder/joomgalleryimages
   - plugins/privacy/joomgalleryimages
   - plugins/webservices/joomgallery
4. Create symbolic links from those folders to the corresponding folders within the checked out copy of your component
5. The referenced copy of your component can be properly versioned using Git

**Symbolic link generator tool for windows:**
https://schinagl.priv.at/nt/hardlinkshellext/linkshellextension.html

### Task areas during development

| Tasks area | Description |
| ----------- | ----------- |
| PHP Developer | Programming the functionalities of the JoomGallery.|
| Language Manager | Setting up, structuring and managing the language files for frontend and backend.|
| Documentation | Writing instructions for the support section of the website.|
| Testing | Testing the new code before merging them into the main project.|

## Testing
1. Open the Pull request you want to test
2. Change to the branch where the code of the PR is coming from
3. Button "Code"->"Download ZIP"
4. Install the zip file in your Joomla
5. Perform tests where the Pull request changes anything
6. If you find a bug or unexpected behaviour, post a comment in the pull request with the following content:

#### Steps to reproduce the issue
List the steps to perform in order to reproduce the issue you found
#### Expected result
What would you have expected should have happen?
#### Actual result
What did really happen?
#### System information
- PHP-Version
- Database type and version
- (ImageMagick version)
#### Additional comments
Anything else that you think is important for the developer to fix the issue
