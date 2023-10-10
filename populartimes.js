import os
from dotenv import load_dotenv
import populartimes
import sys

# Load environment variables from .env
load_dotenv()

# Retrieve the API key from the environment variables
api_key = os.getenv("GOOGLE_API_KEY")

if not api_key:
    raise ValueError("Google API key not found in the .env file")

# Check if the place_id is provided as a command-line argument
if len(sys.argv) < 2:
    print("Usage: python script.py PLACE_ID")
    sys.exit(1)

# Retrieve the place_id from the command-line argument
place_id = sys.argv[1]

# Retrieve popular times data
populartimes_data = populartimes.get_id(api_key, place_id)

if 'populartimes' in populartimes_data:
    popular_times = populartimes_data['populartimes']
    for day in popular_times:
        print(f"Day: {day['name']}, Popular Times: {day['data']}")
else:
    print("No popular times data available for this place.")
