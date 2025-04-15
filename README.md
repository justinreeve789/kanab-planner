# Python virtual environment
In WSL, set up Python3 virtual environment

`python3 -m venv myenv`

`source myenv/bin/activate`

# Install dotenv
`pip3 install dotenv`

# Install populartimes

`pip install --upgrade git+https://github.com/m-wrzr/populartimes`

# Download Chrome Drivers
cd drivers
wget https://storage.googleapis.com/chrome-for-testing-public/135.0.7049.95/linux64/chromedriver-linux64.zip
unzip chromedriver-linux64.zip
mv chromedriver-linux64/chromedriver ./
rm -fr chromedriver-linux64
rm chrome-linux64.zip
chmod a+x chrome-linux
