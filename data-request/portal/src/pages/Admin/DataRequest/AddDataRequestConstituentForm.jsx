import { useReducer, useEffect } from 'react'
import PropTypes from 'prop-types'
import { Form, Button } from 'kit'
import inputValidationMatrix from './inputValidationMatrix'

function inputReducer(state, { name, value }) {
	const newState = { ...state }
	newState[name] = value
	return newState
}

function errorReducer(state, { name, value }) {
	const newState = { ...state }
	const validationFunction = inputValidationMatrix[name]
	newState[name] = validationFunction(value)
	return newState
}

const initialInputState = {
	label: '',
	fileType: -1
}

const initialErrorState = {
	label: undefined,
	fileType: undefined
}

function AddDataRequestConstituentForm({ showing, toggleMethod, constituents, onConstituentUpdate, dataFileTypes, updateDisabled }) {
	const [inputState, updateInputState] = useReducer(inputReducer, initialInputState)
	const [errorState, updateErrorState] = useReducer(errorReducer, initialErrorState)

	const compare = (referenceObject, comparisonObject) => {
		for (const key of Object.keys(referenceObject)) {
			if (!Object.hasOwnProperty.call(comparisonObject, key) || comparisonObject[key] !== referenceObject[key]) {
				return false
			}
		}
		// Checks if comparison object has keys not already checked, if so, they don't exist in 'this' object
		for (const key of Object.keys(comparisonObject)) {
			if (!Object.hasOwnProperty.call(referenceObject, key) || comparisonObject[key] !== referenceObject[key]) {
				return false
			}
		}
		return true
	}

	const addConstituent = () => {
		const formHasErrors = Object.values(errorState).find((val) => !!val)

		if (!formHasErrors && !compare(inputState, initialInputState)) {
			const updatedConstituents = constituents
			updatedConstituents.push({ ...inputState })
			onConstituentUpdate(updatedConstituents)

			updateInputState({ name: 'label', value: null })
			updateInputState({ name: 'fileType', value: -1 })
			toggleMethod()
		}
		else if (compare(inputState, initialInputState)) {
			updateErrorState({ name: 'label', value: true })
		}
	}

	useEffect(() => {
		const initialInput = initialInputState
		for (const field of Object.keys(initialInput)) {
			updateInputState({ name: field, value: initialInput[field] })
		}
	}, [])

	const onChange = ({ target }) => {
		updateInputState({ name: target.name, value: target.value })
	}
	const onBlur = ({ target }) => { updateInputState({ name: target.name, value: target.value }) }

	if (!showing) {
		return (
			<Button className='w-full' variant='outline' onClick={() => toggleMethod()}>Add File to Request</Button>
		)
	}

	return (
		<div className='flex flex-col gap-4 shadow-[0_0_5px_1px_#CCC] p-5 mt-10'>
			<h1>Enter details about the file you&apos;re requesting:</h1>
			<Form.TextArea
				className='w-full'
				name='label'
				label='Label'
				required
				minlength='5'
				onChange={onChange}
				onBlur={onBlur}
				error={!!errorState.label}
				helper={errorState.label}
				ariaErrorMessage={errorState.label}
			/>

			<Form.Select
				name='fileType'
				label='File Format'
				onChange={onChange}
				onBlur={onBlur}
				options={dataFileTypes.data || []}
				className='w-full'
				selectClassName='cursor-pointer file_type_select'
			/>
			<div className='flex flex-col md:flex-row gap-4'>
				<Button variant='outline' className='w-full' onClick={() => addConstituent()} disabled={updateDisabled}>Add</Button>
				<Button variant='outline' className='w-full' onClick={() => toggleMethod()} disabled={updateDisabled}>Cancel</Button>
			</div>
		</div>

	)
}

AddDataRequestConstituentForm.propTypes = {
	showing: PropTypes.bool.isRequired,
	toggleMethod: PropTypes.func.isRequired,
	constituents: PropTypes.arrayOf(PropTypes.shape({
		label: PropTypes.string.isRequired,
		fileType: PropTypes.any.isRequired
	}).isRequired).isRequired,
	onConstituentUpdate: PropTypes.func.isRequired,
	dataFileTypes: PropTypes.shape({
		data: PropTypes.array
	}).isRequired,
	updateDisabled: PropTypes.bool.isRequired
}

export default AddDataRequestConstituentForm
