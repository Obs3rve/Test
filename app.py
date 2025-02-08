from flask import Flask, request, jsonify
import requests
import math  # Import math for rounding floating-point numbers

app = Flask(__name__)

def is_prime(n: int) -> bool:
    """Check if a number is prime."""
    if n < 2:
        return False
    for i in range(2, int(n ** 0.5) + 1):
        if n % i == 0:
            return False
    return True

def is_perfect(n: int) -> bool:
    """Check if a number is a perfect number."""
    return n > 1 and sum(i for i in range(1, n) if n % i == 0) == n

def is_armstrong(n: int) -> bool:
    """Check if a number is an Armstrong number."""
    digits = [int(d) for d in str(abs(n))]  # Take absolute value for digit calculation
    return sum(d ** len(digits) for d in digits) == abs(n)

def get_fun_fact(n: int) -> str:
    """Fetch a fun fact about the number from NumbersAPI."""
    response = requests.get(f"http://numbersapi.com/{n}/math")
    return response.text if response.status_code == 200 else "No fact found."

@app.route("/api/classify-number", methods=["GET"])
def classify_number():
    """API endpoint to classify numbers."""
    number_param = request.args.get("number")

    try:
        # Convert to float first to handle floating-point values
        number = float(number_param)

        # If it's a floating-point number, round it to the nearest integer
        rounded_number = round(number)

        properties = []
        if is_armstrong(rounded_number):
            properties.append("armstrong")
        properties.append("odd" if rounded_number % 2 else "even")

        return jsonify({
            "number": rounded_number,  # Return the rounded number
            "is_prime": is_prime(rounded_number),
            "is_perfect": is_perfect(rounded_number),
            "properties": properties,
            "digit_sum": sum(int(d) for d in str(abs(rounded_number))),  # Use absolute value
            "fun_fact": get_fun_fact(rounded_number)
        }), 200  # Ensure 200 OK for valid numbers

    except ValueError:
        return jsonify({"number": "invalid", "error": True}), 400  # Keep 400 for truly invalid inputs

if __name__ == "__main__":
    app.run(host="0.0.0.0", port=8000)

